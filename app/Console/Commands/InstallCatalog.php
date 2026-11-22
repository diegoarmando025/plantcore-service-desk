<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Team;
use App\Models\TicketCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InstallCatalog extends Command
{
    protected $signature = 'service-desk:install-catalog {--force : Confirma instalación en producción}';
    protected $description = 'Carga únicamente departamentos, equipos y categorías, sin usuarios ni tickets ficticios';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('En producción utiliza --force después de verificar la base MySQL.');
            return self::FAILURE;
        }
        $departments = [['TI','Tecnologías de Información','Incidentes y solicitudes de infraestructura, servidores, aplicaciones y datos'],['RH','Recursos Humanos','Altas, bajas, contratos y constancias'],['FIN','Finanzas','Pagos, facturas y reembolsos'],['COM','Compras','Requisiciones y seguimiento de proveedores'],['MTTO','Mantenimiento','Equipos, servicios y reparaciones']];
        $teams = [['TI','INFRA','Infraestructura y redes'],['TI','SERV','Servidores y sistemas'],['TI','DBA','Bases de datos'],['TI','APP','Soporte de aplicaciones'],['TI','HELP','Mesa de ayuda'],['RH','RH-ADM','Administración RH'],['FIN','TES','Tesorería'],['FIN','CONT','Contabilidad'],['COM','ADQ','Adquisiciones'],['MTTO','TALLER','Soporte de equipos']];
        $services = [['TI','INFRA','NETWORK','Red y conectividad',24],['TI','SERV','SERVER','Servidores y máquinas virtuales',12],['TI','DBA','DATABASE','Bases de datos',8],['TI','APP','APPLICATION','Aplicaciones empresariales',24],['TI','HELP','USER_ACCESS','Alta o cambio de accesos',16],['TI','HELP','PC_SUPPORT','Soporte a equipos',24],['RH','RH-ADM','CONTRACT','Elaboración de contratos',72],['RH','RH-ADM','ONBOARD','Alta de personal',48],['RH','RH-ADM','CERTIFICATE','Constancias laborales',48],['FIN','TES','PAYMENT','Seguimiento de pagos',72],['FIN','CONT','INVOICE','Facturas y aclaraciones',48],['FIN','CONT','EXPENSE','Reembolsos y viáticos',72],['COM','ADQ','REQUISITION','Requisición de materiales',96],['COM','ADQ','SUPPLIER','Alta de proveedor',120],['MTTO','TALLER','REPAIR','Reparación de equipos',48]];

        DB::transaction(function () use ($departments, $teams, $services): void {
            foreach ($departments as [$code, $name, $description]) {
                Department::firstOrCreate(['code' => $code], ['name' => $name, 'description' => $description]);
            }
            foreach ($teams as [$departmentCode, $code, $name]) {
                $department = Department::where('code', $departmentCode)->firstOrFail();
                Team::firstOrCreate(['department_id' => $department->id, 'code' => $code], ['name' => $name]);
            }
            foreach ($services as [$departmentCode, $teamCode, $code, $name, $hours]) {
                $department = Department::where('code', $departmentCode)->firstOrFail();
                $team = Team::where('department_id', $department->id)->where('code', $teamCode)->firstOrFail();
                TicketCategory::firstOrCreate(['department_id' => $department->id, 'code' => $code], [
                    'team_id' => $team->id, 'name' => $name, 'sla_hours' => $hours,
                ]);
            }
        });
        $this->info('Catálogo operativo preparado sin cuentas o tickets de prueba.');
        return self::SUCCESS;
    }
}
