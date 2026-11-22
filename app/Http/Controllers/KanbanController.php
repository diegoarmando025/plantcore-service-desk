<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Support\TicketAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KanbanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        // Reuse the same visibility conditions as ticket inbox; never enumerate
        // operational tickets to portfolio visitors, or demo tickets to staff.
        $visible = Ticket::query();
        if ($user->is_portfolio_demo) {
            $visible->where('is_portfolio_demo', true);
        } elseif (! $user->is_owner) {
            $visible->where('is_portfolio_demo', false);
        }

        if (! $user->isAdmin()) {
            $departmentIds = $user->departments()->pluck('departments.id');
            $visible->where(function ($query) use ($user, $departmentIds) {
                $query->where('requester_id', $user->id);
                if ($user->role === 'agent') {
                    $query->orWhereIn('department_id', $departmentIds);
                }
            });
        }

        $columns = [];
        $counts = [];
        foreach (Ticket::STATUSES as $status) {
            $scope = (clone $visible)->where('status', $status);
            $counts[$status] = (clone $scope)->count();
            $columns[$status] = $scope->with(['department:id,name', 'assignee:id,name'])
                ->orderByDesc('id')->limit(30)->get();
        }

        return view('tickets.kanban', compact('columns', 'counts'));
    }

    public function move(Request $request, Ticket $ticket): RedirectResponse
    {
        TicketAccess::handle($request->user(), $ticket);
        $data = $request->validate([
            'status' => ['required', Rule::in(Ticket::STATUSES)],
        ]);

        DB::transaction(function () use ($request, $ticket, $data): void {
            $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            // Recheck permissions inside transaction so a transfer between
            // departments cannot bypass access controls.
            TicketAccess::handle($request->user(), $locked);
            abort_if($locked->status === 'closed', 409, 'Un ticket cerrado no se puede modificar.');

            $previous = $locked->status;
            if ($previous === $data['status']) return;

            $locked->status = $data['status'];
            if ($data['status'] === 'resolved') {
                $locked->resolved_at ??= now();
            } elseif ($data['status'] === 'closed') {
                $locked->resolved_at ??= now();
                $locked->closed_at = now();
            } else {
                $locked->resolved_at = null;
                $locked->closed_at = null;
            }
            $locked->save();

            $locked->activities()->create([
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'details' => [
                    'source' => 'kanban',
                    'before' => ['status' => $previous],
                    'after' => ['status' => $locked->status],
                ],
            ]);
        }, 3);

        return redirect()->route('tickets.kanban')->with('ok', 'Estado actualizado.');
    }
}
