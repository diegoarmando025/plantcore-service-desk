<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $departmentIds = $user->departments()->pluck('departments.id');

        // Keep all dashboard aggregates inside the exact ticket visibility scope.
        $scope = Ticket::query();
        if ($user->is_portfolio_demo) {
            $scope->where('is_portfolio_demo', true);
        } elseif (! $user->is_owner) {
            $scope->where('is_portfolio_demo', false);
        }
        if (! $user->isAdmin()) {
            $scope->where(function ($query) use ($user, $departmentIds): void {
                $query->where('requester_id', $user->id);
                if ($user->role === 'agent') {
                    $query->orWhereIn('department_id', $departmentIds);
                }
            });
        }

        $counts = [
            'total' => (clone $scope)->count(),
            'open' => (clone $scope)->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'waiting' => (clone $scope)->where('status', 'waiting')->count(),
            'overdue' => (clone $scope)->whereNotIn('status', ['resolved', 'closed'])->where('due_at', '<', now())->count(),
            'resolved' => (clone $scope)->whereIn('status', ['resolved', 'closed'])->count(),
            'unassigned' => (clone $scope)->whereNull('assignee_id')->whereNotIn('status', ['resolved', 'closed'])->count(),
            'due_soon' => (clone $scope)->whereNotIn('status', ['resolved', 'closed'])
                ->whereBetween('due_at', [now(), now()->addHours(24)])->count(),
        ];

        $recent = (clone $scope)
            ->with(['department:id,name', 'assignee:id,name'])
            ->orderByDesc('id')->limit(8)->get();

        $urgent = (clone $scope)->whereNotIn('status', ['resolved', 'closed'])
            ->whereNotNull('due_at')
            ->with(['department:id,name', 'assignee:id,name'])
            ->orderBy('due_at')->limit(6)->get();

        $statusCounts = (clone $scope)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        $workloadRows = (clone $scope)->whereNotIn('status', ['resolved', 'closed'])
            ->whereNotNull('assignee_id')->selectRaw('assignee_id, COUNT(*) as total')
            ->groupBy('assignee_id')->orderByDesc('total')->limit(6)->get();
        $names = User::whereIn('id', $workloadRows->pluck('assignee_id'))
            ->pluck('name', 'id');
        // Never expose account details in a portfolio aggregate; only names/record counts.
        $workload = $workloadRows->map(fn ($row) => [
            'name' => $names[$row->assignee_id] ?? 'Agente',
            'total' => (int) $row->total,
        ]);

        return view('dashboard', compact('counts', 'recent', 'urgent', 'statusCounts', 'workload'));
    }
}
