<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KanbanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $department = Department::create(['code' => 'TI', 'name' => 'Tecnologías de información']);
        $category = TicketCategory::create([
            'code' => 'DB', 'name' => 'Bases de datos',
            'department_id' => $department->id, 'sla_hours' => 24,
        ]);
        $demo = User::create([
            'name' => 'Evaluador', 'email' => 'demo-kanban@example.invalid',
            'password' => 'Test-private-password-2026!', 'role' => 'agent',
        ]);
        $demo->forceFill(['is_portfolio_demo' => true])->save();
        DB::table('department_user')->insert([
            'user_id' => $demo->id, 'department_id' => $department->id, 'role' => 'agent',
        ]);
        $regular = User::create([
            'name' => 'Operador real', 'email' => 'regular-kanban@example.invalid',
            'password' => 'Test-private-password-2026!', 'role' => 'agent',
        ]);
        DB::table('department_user')->insert([
            'user_id' => $regular->id, 'department_id' => $department->id, 'role' => 'agent',
        ]);
        $visitor = User::create([
            'name' => 'Solicitante', 'email' => 'requester-kanban@example.invalid',
            'password' => 'Test-private-password-2026!', 'role' => 'requester',
        ]);
        $demoTicket = Ticket::create([
            'code' => 'SD-000012', 'title' => 'Atención de impresora',
            'description' => 'Incidencia ficticia para el tablero.',
            'requester_id' => $demo->id, 'department_id' => $department->id,
            'ticket_category_id' => $category->id,
            'priority' => 'high', 'status' => 'open', 'is_portfolio_demo' => true,
            'due_at' => now()->addHours(3),
        ]);
        $realTicket = Ticket::create([
            'code' => 'SD-000013', 'title' => 'Ticket interno confidencial',
            'description' => 'Solicitud real y reservada.',
            'requester_id' => $visitor->id, 'department_id' => $department->id,
            'ticket_category_id' => $category->id,
            'priority' => 'critical', 'status' => 'open', 'is_portfolio_demo' => false,
        ]);
        return compact('demo', 'regular', 'visitor', 'demoTicket', 'realTicket');
    }

    public function test_demo_board_is_accessible_and_does_not_expose_real_tickets(): void
    {
        extract($this->fixture());
        $this->actingAs($demo)->get('/tickets/kanban')->assertOk()
            ->assertSee('Tablero de solicitudes')
            ->assertSee('Atención de impresora')
            ->assertDontSee('Ticket interno confidencial');
        $this->actingAs($regular)->get('/tickets/kanban')->assertOk()
            ->assertSee('Ticket interno confidencial')
            ->assertDontSee('Atención de impresora');
    }

    public function test_demo_can_change_state_and_activity_is_recorded(): void
    {
        extract($this->fixture());
        $this->actingAs($demo)->patch('/tickets/'.$demoTicket->id.'/kanban', [
            'status' => 'in_progress',
        ])->assertRedirect('/tickets/kanban');
        $this->assertDatabaseHas('tickets', [
            'id' => $demoTicket->id, 'status' => 'in_progress',
            'is_portfolio_demo' => true,
        ]);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $demoTicket->id, 'user_id' => $demo->id, 'action' => 'updated',
        ]);

        $this->actingAs($demo)->patch('/tickets/'.$demoTicket->id.'/kanban', [
            'status' => 'resolved',
        ])->assertRedirect('/tickets/kanban');
        $this->assertNotNull($demoTicket->fresh()->resolved_at);
        $this->actingAs($demo)->patch('/tickets/'.$demoTicket->id.'/kanban', [
            'status' => 'open',
        ])->assertRedirect('/tickets/kanban');
        $this->assertNull($demoTicket->fresh()->resolved_at);
    }

    public function test_demo_cannot_move_operational_ticket_and_regular_agent_cannot_move_demo_ticket(): void
    {
        extract($this->fixture());
        $this->actingAs($demo)->patch('/tickets/'.$realTicket->id.'/kanban', [
            'status' => 'closed',
        ])->assertNotFound();
        $this->actingAs($regular)->patch('/tickets/'.$demoTicket->id.'/kanban', [
            'status' => 'closed',
        ])->assertNotFound();
        $this->assertSame('open', $realTicket->fresh()->status);
        $this->assertSame('open', $demoTicket->fresh()->status);
    }

    public function test_requester_cannot_modify_ticket_and_closed_state_is_final(): void
    {
        extract($this->fixture());
        $this->actingAs($visitor)->patch('/tickets/'.$realTicket->id.'/kanban', [
            'status' => 'resolved',
        ])->assertNotFound();
        $this->actingAs($demo)->patch('/tickets/'.$demoTicket->id.'/kanban', [
            'status' => 'not-a-state',
        ])->assertSessionHasErrors('status');
        $this->actingAs($demo)->patch('/tickets/'.$demoTicket->id.'/kanban', [
            'status' => 'closed',
        ])->assertRedirect('/tickets/kanban');
        $this->assertNotNull($demoTicket->fresh()->closed_at);
        $this->actingAs($demo)->patch('/tickets/'.$demoTicket->id.'/kanban', [
            'status' => 'open',
        ])->assertStatus(409);
    }
}
