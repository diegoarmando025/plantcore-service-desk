<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function setupDemo(): array
    {
        $dept = Department::create(['name' => 'Tecnología', 'code' => 'TI']);
        $category = TicketCategory::create([
            'department_id' => $dept->id, 'name' => 'Incidencias',
            'code' => 'INC', 'sla_hours' => 24,
        ]);
        $demo = User::create([
            'name' => 'Evaluador', 'email' => 'demo@example.invalid',
            'password' => 'Test-password-private-2026!', 'role' => 'agent',
        ]);
        $demo->forceFill(['is_portfolio_demo' => true])->save();
        DB::table('department_user')->insert([
            'department_id' => $dept->id, 'user_id' => $demo->id, 'role' => 'agent',
        ]);
        $privateUser = User::create([
            'name' => 'Usuario privado', 'email' => 'private@example.invalid',
            'password' => 'Test-password-private-2026!', 'role' => 'requester',
        ]);

        $public = Ticket::create([
            'title' => 'Demo para comprobar SLA',
            'description' => 'Incidencia ficticia de un operador',
            'requester_id' => $demo->id,
            'assignee_id' => $demo->id,
            'department_id' => $dept->id,
            'ticket_category_id' => $category->id,
            'status' => 'open',
            'priority' => 'high',
            'is_portfolio_demo' => true,
            'due_at' => now()->subHour(),
        ]);
        $public->update(['code' => 'SD-'.str_pad((string)$public->id, 6, '0', STR_PAD_LEFT)]);

        $private = Ticket::create([
            'title' => 'Ticket privado que no debe mostrarse',
            'description' => 'Operación interna ajena a la demo',
            'requester_id' => $privateUser->id,
            'department_id' => $dept->id,
            'ticket_category_id' => $category->id,
            'status' => 'open',
            'priority' => 'critical',
            'due_at' => now()->subDay(),
        ]);

        return compact('demo', 'privateUser', 'public', 'private');
    }

    public function test_demo_dashboard_shows_real_scoped_sla_and_workload_metrics(): void
    {
        extract($this->setupDemo());
        $response = $this->actingAs($demo)->get('/dashboard');
        $response->assertOk()
            ->assertSee('Resumen de solicitudes')
            ->assertSee('Fuera de plazo')
            ->assertSee('Carga por agente')
            ->assertSee('Demo para comprobar SLA')
            ->assertSee('Evaluador')
            ->assertDontSee('Ticket privado que no debe mostrarse');
        $response->assertViewHas('counts', function ($counts) {
            return $counts['total'] === 1
                && $counts['overdue'] === 1
                && $counts['open'] === 1
                && $counts['due_soon'] === 0;
        });
        $response->assertViewHas('workload', fn ($agents) => $agents->count() === 1
            && $agents->first()['total'] === 1);
    }

    public function test_demo_ticket_filters_do_not_disclose_private_tickets(): void
    {
        extract($this->setupDemo());
        $this->actingAs($demo)->get('/tickets?priority=critical')
            ->assertOk()
            ->assertDontSee('Ticket privado que no debe mostrarse');
        $this->actingAs($demo)->get('/tickets?priority=high')
            ->assertOk()
            ->assertSee('Demo para comprobar SLA');
        $this->actingAs($demo)->get('/tickets/'.$private->id)->assertNotFound();
        $this->actingAs($demo)->get('/tickets/'.$public->id)
            ->assertOk()
            ->assertSee('Plazo de atención');
    }
}
