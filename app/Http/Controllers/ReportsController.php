<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    private function criteria(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = CarbonImmutable::parse($data['from'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($data['to'] ?? now()->toDateString())->endOfDay();
        if ($from->greaterThan($to) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['from' => 'Selecciona un rango máximo de 366 días.']);
        }
        return [$from, $to];
    }

    private function scope(Request $request, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        $user = $request->user();
        $query = Ticket::query()->whereBetween('created_at', [$from, $to]);

        if ($user->is_portfolio_demo) {
            $query->where('is_portfolio_demo', true);
        } elseif (! $user->is_owner) {
            $query->where('is_portfolio_demo', false);
        }
        if (! $user->isAdmin()) {
            $departments = $user->departments()->pluck('departments.id');
            $query->where(function (Builder $q) use ($user, $departments): void {
                $q->where('requester_id', $user->id);
                if ($user->role === 'agent') {
                    $q->orWhereIn('department_id', $departments);
                }
            });
        }
        return $query;
    }

    private function data(Request $request): array
    {
        [$from, $to] = $this->criteria($request);
        $scope = $this->scope($request, $from, $to);
        $total = (clone $scope)->count();
        $completed = (clone $scope)->whereIn('status', ['resolved', 'closed'])->whereNotNull('resolved_at');
        $completedCount = (clone $completed)->count();
        // SLA in elapsed hours, comparing resolution timestamp against stored deadline.
        // Tickets without a defined deadline are excluded from SLA calculation.
        $slaEligible = (clone $completed)->whereNotNull('due_at')->count();
        $slaMet = (clone $completed)->whereNotNull('due_at')
            ->whereColumn('resolved_at', '<=', 'due_at')->count();
        // Averages are calculated in PHP for SQLite/MySQL compatibility.
        $durations = (clone $completed)->get(['created_at', 'resolved_at'])->map(
            fn ($ticket) => max(0, $ticket->created_at->diffInMinutes($ticket->resolved_at) / 60)
        );
        $avgHours = $durations->count() > 0 ? round($durations->avg(), 1) : null;
        $overdue = (clone $scope)->whereNotIn('status', ['resolved', 'closed'])
            ->whereNotNull('due_at')->where('due_at', '<', now())->count();

        $departmentRows = (clone $scope)->selectRaw('department_id, COUNT(*) as tickets')
            ->groupBy('department_id')->orderByDesc('tickets')->get();
        $departmentNames = DB::table('departments')->whereIn('id', $departmentRows->pluck('department_id'))
            ->pluck('name', 'id');
        $departments = $departmentRows->map(fn ($row) => [
            'name' => $departmentNames[$row->department_id] ?? 'Sin departamento',
            'tickets' => (int) $row->tickets,
        ])->all();

        $agentRows = (clone $scope)->whereNotNull('assignee_id')
            ->selectRaw('assignee_id, COUNT(*) as tickets')
            ->groupBy('assignee_id')->orderByDesc('tickets')->get();
        $agentNames = User::query()->whereIn('id', $agentRows->pluck('assignee_id'))
            ->pluck('name', 'id');
        $agents = $agentRows->map(fn ($row) => [
            'name' => $agentNames[$row->assignee_id] ?? 'Agente',
            'tickets' => (int) $row->tickets,
        ])->all();

        $statuses = [];
        foreach (Ticket::STATUSES as $status) {
            $statuses[] = [
                'name' => Ticket::STATUS_LABELS[$status],
                'tickets' => (clone $scope)->where('status', $status)->count(),
            ];
        }

        return compact('from', 'to', 'total', 'completedCount', 'slaEligible', 'slaMet', 'avgHours', 'overdue', 'departments', 'agents', 'statuses');
    }

    public function index(Request $request): View
    {
        return view('reports.index', $this->data($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->data($request);
        // Export aggregates rather than raw ticket descriptions or requester PII.
        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM helps Excel display Spanish text.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['PlantCore Service Desk', 'Reporte de operaciones']);
            fputcsv($handle, ['Desde', $data['from']->toDateString(), 'Hasta', $data['to']->toDateString()]);
            fputcsv($handle, ['Solicitudes', $data['total']]);
            fputcsv($handle, ['Resueltas', $data['completedCount']]);
            fputcsv($handle, ['Fuera de plazo', $data['overdue']]);
            fputcsv($handle, ['SLA cumplidos', $data['slaMet'], 'SLA evaluables', $data['slaEligible']]);
            fputcsv($handle, ['Promedio resolución (horas)', $data['avgHours'] ?? 'Sin datos']);
            foreach (['Estado' => 'statuses', 'Departamento' => 'departments', 'Agente' => 'agents'] as $label => $key) {
                fputcsv($handle, []);
                fputcsv($handle, [$label, 'Tickets']);
                foreach ($data[$key] as $row) {
                    $name = $row['name'];
                    // Prevent CSV formula injection when names come from editable database fields.
                    if (preg_match('/^[\s]*[=+\-@]/u', $name)) $name = "'".$name;
                    fputcsv($handle, [$name, $row['tickets']]);
                }
            }
            fclose($handle);
        }, 'service-desk-reporte-'.$data['from']->format('Ymd').'-'.$data['to']->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
