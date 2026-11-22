<?php
namespace Database\Seeders;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder {
 public function run():void {
  // Never seed public passwords or fictional employee data in production.
  abort_unless(app()->environment(['local','testing']),403,'El escenario de demostración solo está permitido en local/testing.');
  DB::transaction(function(){
   $admin=User::firstOrCreate(['email'=>'admin@plantcore.test'],['name'=>'Administrador PlantCore','password'=>'PlantCoreDemo2026!','role'=>'admin']);
   $requester=User::firstOrCreate(['email'=>'empleado@plantcore.test'],['name'=>'María Rodríguez','password'=>'PlantCoreDemo2026!','role'=>'requester']);
   $agentData=[['infra@plantcore.test','Daniel Torres','Infraestructura','TI'],['db@plantcore.test','Ana Medina','Bases de datos','TI'],['apps@plantcore.test','Luis Herrera','Aplicaciones','TI'],['rh@plantcore.test','Laura Méndez','Administración RH','RH'],['finanzas@plantcore.test','Pablo Ríos','Tesorería','FIN'],['compras@plantcore.test','Sofía Cruz','Adquisiciones','COM']];
   $departments=[['TI','Tecnologías de Información','Incidentes y solicitudes de infraestructura, servidores, aplicaciones y datos'],['RH','Recursos Humanos','Altas, bajas, contratos y constancias'],['FIN','Finanzas','Pagos, facturas y reembolsos'],['COM','Compras','Requisiciones y seguimiento de proveedores'],['MTTO','Mantenimiento','Equipos, servicios y reparaciones']];
   foreach($departments as [$code,$name,$description])Department::firstOrCreate(['code'=>$code],['name'=>$name,'description'=>$description]);
   $teamDefinitions=[['TI','INFRA','Infraestructura y redes'],['TI','SERV','Servidores y sistemas'],['TI','DBA','Bases de datos'],['TI','APP','Soporte de aplicaciones'],['TI','HELP','Mesa de ayuda'],['RH','RH-ADM','Administración RH'],['FIN','TES','Tesorería'],['FIN','CONT','Contabilidad'],['COM','ADQ','Adquisiciones'],['MTTO','TALLER','Soporte de equipos']];
   foreach($teamDefinitions as [$depCode,$code,$name]){ $dep=Department::where('code',$depCode)->firstOrFail(); Team::firstOrCreate(['department_id'=>$dep->id,'code'=>$code],['name'=>$name]); }
   $services=[['TI','INFRA','NETWORK','Red y conectividad',24],['TI','SERV','SERVER','Servidores y máquinas virtuales',12],['TI','DBA','DATABASE','Bases de datos',8],['TI','APP','APPLICATION','Aplicaciones empresariales',24],['TI','HELP','USER_ACCESS','Alta o cambio de accesos',16],['TI','HELP','PC_SUPPORT','Soporte a equipos',24],['RH','RH-ADM','CONTRACT','Elaboración de contratos',72],['RH','RH-ADM','ONBOARD','Alta de personal',48],['RH','RH-ADM','CERTIFICATE','Constancias laborales',48],['FIN','TES','PAYMENT','Seguimiento de pagos',72],['FIN','CONT','INVOICE','Facturas y aclaraciones',48],['FIN','CONT','EXPENSE','Reembolsos y viáticos',72],['COM','ADQ','REQUISITION','Requisición de materiales',96],['COM','ADQ','SUPPLIER','Alta de proveedor',120],['MTTO','TALLER','REPAIR','Reparación de equipos',48]];
   foreach($services as [$depCode,$teamCode,$code,$name,$sla]) { $d=Department::where('code',$depCode)->firstOrFail();$t=Team::where('department_id',$d->id)->where('code',$teamCode)->firstOrFail();TicketCategory::firstOrCreate(['department_id'=>$d->id,'code'=>$code],['team_id'=>$t->id,'name'=>$name,'sla_hours'=>$sla]); }
   $agents=[];
   foreach($agentData as [$email,$name,$teamName,$depCode]){
    $agent=User::firstOrCreate(['email'=>$email],['name'=>$name,'password'=>'PlantCoreDemo2026!','role'=>'agent']);
    $dep=Department::where('code',$depCode)->firstOrFail();$team=Team::where('department_id',$dep->id)->where('name',$teamName)->first();
    // Team names may differ in display; map by explicit email.
    $code=match($email){'infra@plantcore.test'=>'INFRA','db@plantcore.test'=>'DBA','apps@plantcore.test'=>'APP','rh@plantcore.test'=>'RH-ADM','finanzas@plantcore.test'=>'TES',default=>'ADQ'};
    $team=Team::where('department_id',$dep->id)->where('code',$code)->firstOrFail();
    DB::table('department_user')->updateOrInsert(['department_id'=>$dep->id,'user_id'=>$agent->id],['role'=>'agent']);
    DB::table('team_user')->updateOrInsert(['team_id'=>$team->id,'user_id'=>$agent->id],[]);
    $agents[$email]=$agent;
   }
   $examples=[
    ['TI','DATABASE','Validar lentitud en consultas MySQL','Los reportes de producción tardan más de veinte segundos después de las 14:00. Revisar índices y uso de recursos.','high','in_progress','db@plantcore.test',-5],
    ['TI','SERVER','Revisar espacio disponible en servidor de aplicaciones','El volumen de respaldo alcanzó el 85% de capacidad y requiere revisión de crecimiento.','critical','assigned','infra@plantcore.test',-1],
    ['TI','APPLICATION','Error al generar archivo CSV de producción','Al exportar órdenes del turno se recibe un error y no se descarga el archivo.','high','waiting','apps@plantcore.test',-2],
    ['TI','NETWORK','Intermitencia de red en almacén','Las terminales del área de almacén pierden comunicación varias veces por turno.','normal','open',null,-1],
    ['TI','USER_ACCESS','Crear acceso de lectura para analista nuevo','Solicito una cuenta de consulta en el sistema de indicadores para el personal de reciente ingreso.','normal','resolved','apps@plantcore.test',-4],
    ['RH','CONTRACT','Preparación de contrato para nuevo ingreso','Solicito elaborar contrato laboral para el nuevo auxiliar administrativo con fecha de ingreso próxima.','normal','in_progress','rh@plantcore.test',-2],
    ['RH','ONBOARD','Alta de expediente de colaborador','Registrar información inicial y checklist de documentos pendientes de un nuevo colaborador.','normal','assigned','rh@plantcore.test',-1],
    ['RH','CERTIFICATE','Solicitar constancia laboral','Necesito una constancia laboral con puesto y antigüedad para un trámite personal.','low','closed','rh@plantcore.test',-10],
    ['FIN','INVOICE','Aclaración de factura de proveedor','La factura recibida presenta diferencia entre la orden de compra y el importe facturado.','normal','waiting','finanzas@plantcore.test',-3],
    ['FIN','PAYMENT','Validar calendario de pagos','Se requiere confirmación de la fecha de pago de las órdenes pendientes del proveedor ficticio.','high','open',null,-1],
    ['FIN','EXPENSE','Reembolso de viáticos','Registrar y revisar comprobantes de traslado por visita a planta en Querétaro.','normal','resolved','finanzas@plantcore.test',-7],
    ['COM','REQUISITION','Solicitar compra de lector de código de barras','Se necesita lector para estación de empaque a fin de validar el número de serie antes de embarque.','high','assigned','compras@plantcore.test',-2],
    ['COM','SUPPLIER','Alta de proveedor de material indirecto','Solicitar revisión de datos administrativos del proveedor de consumibles de mantenimiento.','normal','open',null,-1],
    ['MTTO','REPAIR','Revisar equipo de impresión Zebra','La impresora presenta etiquetas desplazadas y requiere ajuste de calibración.','high','open',null,-1]
   ];
   foreach($examples as $i=>$item){[$depCode,$categoryCode,$title,$body,$priority,$status,$email,$dayOffset]=$item;$dep=Department::where('code',$depCode)->firstOrFail();$cat=TicketCategory::where('department_id',$dep->id)->where('code',$categoryCode)->firstOrFail();
    $ticket=Ticket::firstOrCreate(['title'=>$title,'requester_id'=>$requester->id],['description'=>$body,'department_id'=>$dep->id,'team_id'=>$cat->team_id,'ticket_category_id'=>$cat->id,'assignee_id'=>$email?($agents[$email]->id):null,'priority'=>$priority,'status'=>$status,'due_at'=>now()->addDays($dayOffset)->addHours($cat->sla_hours),'resolved_at'=>in_array($status,['resolved','closed'])?now()->subDay():null,'closed_at'=>$status==='closed'?now():null]);
    if(!$ticket->code)$ticket->update(['code'=>'SD-'.str_pad((string)$ticket->id,6,'0',STR_PAD_LEFT)]);
    $ticket->activities()->firstOrCreate(['user_id'=>$requester->id,'action'=>'created'],['details'=>['category_id'=>$cat->id]]);
    if($email){$ticket->messages()->firstOrCreate(['user_id'=>$agents[$email]->id,'body'=>'Solicitud recibida. Estamos validando la información y compartiremos el avance por este medio.'],['internal'=>false]);}
   }
  });
 }
}
