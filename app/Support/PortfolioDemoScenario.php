<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
                'ticket_category_id' => $category->id, 'assignee_id' => $status === 'open' ? null : $demo->id,
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
        return DB::transaction(function () use ($demo): int {
            $ids = Ticket::where('is_portfolio_demo', true)->pluck('id');
            $count = $ids->count();
            DB::table('ticket_messages')->whereIn('ticket_id', $ids)->delete();
            DB::table('ticket_activities')->whereIn('ticket_id', $ids)->delete();
            Ticket::whereIn('id', $ids)->where('is_portfolio_demo', true)->delete();
            self::seed($demo);
            return $count;
        }, 3);
    }
}
