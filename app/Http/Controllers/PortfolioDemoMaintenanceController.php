<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Support\PortfolioDemoScenario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class PortfolioDemoMaintenanceController extends Controller
{
    public function complete(Request $request): RedirectResponse
    {
        // Maintenance of shared fictional records is never available to public reviewers.
        abort_unless($request->user()->is_owner && $request->user()->isAdmin()
            && ! $request->user()->is_portfolio_demo, 403);

        $request->validate([
            'confirmation' => ['required', 'in:COMPLETAR DEMO'],
        ]);

        try {
            $added = DB::transaction(function (): int {
                // Serialize simultaneous maintenance requests on the dedicated demo identity.
                $demo = User::query()
                    ->where('email', 'portfolio.demo@service-desk.example.invalid')
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $demo->is_portfolio_demo || $demo->is_owner
                    || ! $demo->active || $demo->role !== 'agent') {
                    throw new RuntimeException('La cuenta de evaluación no tiene un estado válido.');
                }

                // The seeder is additive: it skips existing fictional tickets by title
                // and does not reset tickets, messages, attachments or the owner account.
                $before = Ticket::query()->where('is_portfolio_demo', true)->count();
                PortfolioDemoScenario::seed($demo);
                $after = Ticket::query()->where('is_portfolio_demo', true)->count();

                return $after - $before;
            }, 3);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'demo_maintenance' => 'No se pudo completar el escenario. Verifica los catálogos y consulta los registros del sistema.',
            ]);
        }

        Log::info('Portfolio demo scenario topped up by owner', [
            'owner_id' => $request->user()->id,
            'new_tickets' => $added,
        ]);

        return back()->with('ok', $added > 0
            ? "Se añadieron {$added} solicitudes ficticias. Los registros existentes se conservaron."
            : 'El escenario ya contiene los ejemplos configurados. No se añadieron solicitudes.');
    }
}
