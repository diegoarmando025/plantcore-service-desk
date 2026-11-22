<?php
declare(strict_types=1);
// CLI-only migration utility. Source SQLite and .env remain unchanged.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
if (PHP_SAPI !== 'cli') exit(2);
$mode=$argv[1]??'';
if (!in_array($mode,['check','prepare','copy','verify'],true)) {fwrite(STDERR,"Usage: php scripts/sqlite-to-mysql.php [check|prepare|copy|verify]\n");exit(2);}
$root=dirname(__DIR__);
foreach (['artisan','vendor/autoload.php','bootstrap/app.php'] as $p) if (!is_file("$root/$p")) {fwrite(STDERR,"Missing: $p\n");exit(2);}
$name=getenv('MIGRATION_DB_NAME')?:'';
$expected=getenv('MIGRATION_EXPECTED_DATABASE')?:'';
$user=getenv('MIGRATION_DB_USER')?:'';
$pass=getenv('MIGRATION_DB_PASSWORD');
$host=getenv('MIGRATION_DB_HOST')?:'localhost';
$port=getenv('MIGRATION_DB_PORT')?:'3306';
if (!$name || $name!==$expected || !$user || $pass===false || !ctype_digit($port) ||
    !preg_match('/^[a-zA-Z0-9_]+$/D',$name) || !preg_match('/^[a-zA-Z0-9_.-]+$/D',$host)) {
    fwrite(STDERR,"Invalid or missing migration settings.\n");exit(2);
}
$source="$root/database/database.sqlite";
if (!is_file($source)) {fwrite(STDERR,"SQLite source not found.\n");exit(2);}
try {
    $sqlite=new PDO("sqlite:$source",null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $mysql=new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",$user,$pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    if ($mysql->query('SELECT DATABASE()')->fetchColumn()!==$expected) throw new RuntimeException('Unexpected destination database.');
    if (strtolower((string)$sqlite->query('PRAGMA integrity_check')->fetchColumn())!=='ok') throw new RuntimeException('SQLite integrity check failed.');
    if ($sqlite->query('PRAGMA foreign_key_check')->fetch(PDO::FETCH_NUM)) throw new RuntimeException('Broken source foreign keys.');
    $src=$sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $dst=$mysql->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    // Runtime caches, failed jobs and sessions are not persistent business records.
    $ignored=['migrations','cache','cache_locks','sessions','jobs','job_batches','failed_jobs','password_reset_tokens'];
    $tables=array_values(array_diff($src,$ignored));
    $quote=static function(string $s):string {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D',$s)) throw new RuntimeException('Unsafe identifier.');
        return chr(96).$s.chr(96);
    };
    $count=static fn(PDO $db,string $table):int=>(int)$db->query('SELECT COUNT(*) FROM '.$quote($table))->fetchColumn();
    if ($mode==='check') {
        echo "SQLite integrity: OK | SQLite tables: ".count($src)." | MySQL tables: ".count($dst).PHP_EOL;
        foreach ($tables as $t) echo "$t: ".$count($sqlite,$t)." source rows\n";
        exit(0);
    }
    if (getenv('MIGRATION_BACKUP_CONFIRMED')!=='yes') throw new RuntimeException('Verify a consistent source backup first.');
    if ($mode!=='verify' && getenv('MIGRATION_ALLOW_WRITE')!=='yes') throw new RuntimeException('MIGRATION_ALLOW_WRITE=yes required.');
    if ($mode==='prepare') {
        if ($dst) throw new RuntimeException('Destination already has tables.');
        require "$root/vendor/autoload.php";
        $app=require "$root/bootstrap/app.php";
        $app->make(Kernel::class)->bootstrap();
        $cfg=(array)config('database.connections.mysql',[]);
        config()->set('database.connections.mysql',array_replace($cfg,[
            'driver'=>'mysql','url'=>null,'host'=>$host,'port'=>$port,
            'database'=>$name,'username'=>$user,'password'=>$pass,'unix_socket'=>'',
        ]));
        DB::purge('mysql');
        $code=Artisan::call('migrate',['--database'=>'mysql','--force'=>true,'--no-interaction'=>true]);
        if ($code!==0) throw new RuntimeException('Schema migration failed; inspect destination before retry.');
        echo "Schema created in MySQL. Source SQLite unchanged.\n";
        exit(0);
    }
    foreach ($tables as $t) {
        if (!in_array($t,$dst,true)) throw new RuntimeException("Destination missing $t");
        $from=array_column($sqlite->query('PRAGMA table_info('.$quote($t).')')->fetchAll(PDO::FETCH_ASSOC),'name');
        $to=array_column($mysql->query('SHOW COLUMNS FROM '.$quote($t))->fetchAll(PDO::FETCH_ASSOC),'Field');
        sort($from);sort($to);
        if ($from!==$to) throw new RuntimeException("Column mismatch in $t; no data copied.");
    }
    if ($mode==='copy') {
        foreach ($tables as $t) if ($count($mysql,$t)) throw new RuntimeException("Destination $t is not empty.");
        $pending=$tables;$ordered=[];
        while ($pending) {
            $progress=false;
            foreach ($pending as $index=>$t) {
                $fks=$sqlite->query('PRAGMA foreign_key_list('.$quote($t).')')->fetchAll(PDO::FETCH_ASSOC);
                $deps=array_values(array_diff(array_unique(array_column($fks,'table')),[$t,'migrations']));
                if (!array_diff($deps,$ordered)) {$ordered[]=$t;unset($pending[$index]);$progress=true;}
            }
            if (!$progress) throw new RuntimeException('Unresolved/cyclic table relationships.');
        }
        $sqlite->beginTransaction();$mysql->beginTransaction();
        try {
            foreach ($ordered as $t) {
                $query=$sqlite->query('SELECT * FROM '.$quote($t));
                $first=$query->fetch(PDO::FETCH_ASSOC);
                if (!$first) {echo "$t: 0 rows\n";continue;}
                $cols=array_keys($first);
                $insert=$mysql->prepare('INSERT INTO '.$quote($t).' ('.implode(',',array_map($quote,$cols)).
                    ') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')');
                $copied=0;
                do {$insert->execute(array_values($first));$copied++;} while ($first=$query->fetch(PDO::FETCH_ASSOC));
                if ($copied!==$count($sqlite,$t) || $copied!==$count($mysql,$t)) throw new RuntimeException("Count mismatch: $t");
                echo "$t: $copied rows\n";
            }
            $mysql->commit();$sqlite->commit();
        } catch (Throwable $e) {
            if ($mysql->inTransaction()) $mysql->rollBack();
            if ($sqlite->inTransaction()) $sqlite->rollBack();
            throw $e;
        }
        echo "COPY COMPLETE. Run verify before changing DB_CONNECTION.\n";exit(0);
    }
    $bad=false;
    foreach ($tables as $t) {
        $a=$count($sqlite,$t);$b=$count($mysql,$t);
        echo "$t: SQLite=$a MySQL=$b".($a===$b?" OK":" MISMATCH").PHP_EOL;
        $bad=$bad||$a!==$b;
        if ($a && in_array('id',array_column($sqlite->query('PRAGMA table_info('.$quote($t).')')->fetchAll(PDO::FETCH_ASSOC),'name'),true)) {
            $query='SELECT COALESCE(SUM(id),0) FROM '.$quote($t);
            if ((string)$sqlite->query($query)->fetchColumn()!==(string)$mysql->query($query)->fetchColumn()) {
                echo "  ID checksum mismatch\n";$bad=true;
            }
        }
    }
    if ($bad) throw new RuntimeException('Verification failed. Keep SQLite connection.');
    echo "VERIFIED: counts and ID checks match. Validate login after cutover.\n";
} catch (Throwable $e) {
    fwrite(STDERR,"MIGRATION STOPPED: ".$e->getMessage().PHP_EOL);exit(1);
}
