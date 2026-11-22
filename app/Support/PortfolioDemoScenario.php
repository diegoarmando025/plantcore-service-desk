<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PortfolioDemoScenario {
    public static function seed(User $demo): void {
        if (! $demo->is_portfolio_demo || $demo->is_owner || $demo->role !== 'agent') {
            throw new RuntimeException('Invalid demo identity.');
        }
        // Never touch tickets that are not explicitly flagged as portfolio demo.
        $examples = [
            ['TI', 'DATABASE', 'Revisar lentitud de consultas MySQL', 'Los reportes de operaciones presentan tiempos de respuesta elevados. Revisar índices y consultas.', 'high', 'in_progress'],
            ['TI', 'NETWORK', 'Intermitencia en red de almacén', 'Las estaciones de captura reportan pérdidas de conexión durante el turno.', 'critical', 'open'],
            ['TI', 'APPLICATION', 'Error al exportar informe de producción', 'Al descargar el informe semanal aparece un mensaje de error.', 'normal', 'waiting'],
            ['RH', 'CONTRACT', 'Preparar contrato de nuevo ingreso', 'Solicito elaborar el contrato del personal incorporado a la operación.', 'normal', 'assigned'],
            ['FIN', 'INVOICE', 'Revisión de factura de proveedor', 'Hay una diferencia entre el importe de la orden de compra y la factura.', 'high', 'resolved'],
            ['COM', 'REQUISITION', 'Requisición de lectores de código de barras', 'Solicitar la compra de lectores para una nueva estación de empaque.', 'normal', 'open'],
            ['MTTO', 'REPAIR', 'Calibración de impresora de etiquetas', 'La impresora Zebra muestra desplazamientos al imprimir etiquetas de producción.', 'high', 'open'],
        ];
        // Fictional agents can receive assignments, but have random unknown passwords.
        // They never get access to operational tickets or owner permissions.
        $agents = [];
        foreach ([
            'TI' => ['Soporte de TI', 'ti'],
            'RH' => ['Atención RH', 'rh'],
            'FIN' => ['Analista de finanzas', 'fin'],
            'COM' => ['Compras y proveedores', 'com'],
            'MTTO' => ['Técnico de mantenimiento', 'mtto'],
        ] as $departmentCode => [$agentName, $suffix]) {
            $department = Department::where('code', $departmentCode)->first();
            if (! $department) throw new RuntimeException('Missing demo department: '.$departmentCode);
            $email = 'portfolio.'.$suffix.'@service-desk.example.invalid';
            $agent = User::where('email', $email)->first();
            if ($agent && (! $agent->is_portfolio_demo || $agent->is_owner)) {
                throw new RuntimeException('Reserved fictional agent email belongs to another account.');
            }
            if (! $agent) {
                $agent = User::create([
                    'name' => $agentName, 'email' => $email,
                    'password' => \Illuminate\Support\Str::random(64),
                    'role' => 'agent', 'active' => true,
                ]);
                $agent->forceFill(['is_portfolio_demo' => true])->save();
            }
            if (! $agent->active || $agent->role !== 'agent') {
                throw new RuntimeException('Unexpected demo agent state.');
            }
            DB::table('department_user')->updateOrInsert(
                ['department_id' => $department->id, 'user_id' => $agent->id],
                ['role' => 'agent']
            );
            $teamIds = DB::table('teams')->where('department_id', $department->id)->pluck('id');
            foreach ($teamIds as $teamId) {
                DB::table('team_user')->updateOrInsert(['team_id' => $teamId, 'user_id' => $agent->id], []);
            }
            $agents[$departmentCode] = $agent;
        }

        foreach ($examples as [$departmentCode, $categoryCode, $title, $body, $priority, $status]) {
            $department = Department::where('code', $departmentCode)->first();
            $category = $department ? TicketCategory::where('department_id', $department->id)
                ->where('code', $categoryCode)->first() : null;
            if (! $department || ! $category) throw new RuntimeException('Missing Service Desk catalog for demonstration.');
            $exists = Ticket::where('is_portfolio_demo', true)->where('requester_id', $demo->id)
                ->where('title', $title)->exists();
            if ($exists) continue;
            $ticket = Ticket::create([
                'title' => $title, 'description' => $body, 'requester_id' => $demo->id,
                'department_id' => $department->id, 'team_id' => $category->team_id,
                'ticket_category_id' => $category->id, 'assignee_id' => $status === 'open' ? null : $agents[$departmentCode]->id,
                'priority' => $priority, 'status' => $status, 'due_at' => now()->addHours($category->sla_hours),
                'resolved_at' => $status === 'resolved' ? now() : null, 'is_portfolio_demo' => true,
            ]);
            $ticket->update(['code' => 'SD-'.str_pad((string)$ticket->id, 6, '0', STR_PAD_LEFT)]);
            $ticket->activities()->create([
                'user_id' => $demo->id, 'action' => 'created',
                'details' => ['demo' => true, 'department_id' => $department->id],
            ]);
            $ticket->messages()->create([
                'user_id' => $demo->id, 'body' => 'Solicitud de ejemplo lista para su revisión.',
                'department_id' => $department->id, 'internal' => false,
            ]);
        }
    }

    public static function reset(User $demo): int {
        if (! $demo->is_portfolio_demo || $demo->is_owner) throw new RuntimeException('Invalid demo identity.');
        // Capture private file paths first; remove files only after a successful DB reset.
        // The attachments table cascades with explicitly flagged demo tickets only.
        $paths = TicketAttachment::query()
            ->whereHas('ticket', fn ($query) => $query->where('is_portfolio_demo', true))
            ->pluck('path')->all();

        $deleted = DB::transaction(function () use ($demo): int {
            $ids = Ticket::where('is_portfolio_demo', true)->pluck('id');
            $count = $ids->count();
            DB::table('ticket_messages')->whereIn('ticket_id', $ids)->delete();
            DB::table('ticket_activities')->whereIn('ticket_id', $ids)->delete();
            Ticket::whereIn('id', $ids)->where('is_portfolio_demo', true)->delete();
            self::seed($demo);
            return $count;
        }, 3);

        if ($paths && ! Storage::disk('local')->delete($paths)) {
            report(new RuntimeException('Demo reset succeeded in DB, but private attachment cleanup failed.'));
        }
        return $deleted;
    }
}
