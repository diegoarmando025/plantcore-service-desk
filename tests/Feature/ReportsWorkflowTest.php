<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $department = Department::create(['name'=>'Tecnología','code'=>'TI']);
        $category = TicketCategory::create([
            'department_id'=>$department->id, 'name'=>'Incidencias',
            'code'=>'INC','sla_hours'=>24,
        ]);
        $demo = User::create([
            'name'=>'Evaluador', 'email'=>'reports-demo@example.invalid',
            'password'=>'Long-Private-Password-2026!', 'role'=>'agent',
        ]);
        $demo->forceFill(['is_portfolio_demo'=>true])->save();
        $agent = User::create([
            'name'=>'Agente operativo','email'=>'reports-agent@example.invalid',
            'password'=>'Long-Private-Password-2026!', 'role'=>'agent',
        ]);
        $requester = User::create([
            'name'=>'Solicitante privado','email'=>'reports-req@example.invalid',
            'password'=>'Long-Private-Password-2026!', 'role'=>'requester',
        ]);
        DB::table('department_user')->insert([
            ['user_id'=>$demo->id,'department_id'=>$department->id,'role'=>'agent'],
            ['user_id'=>$agent->id,'department_id'=>$department->id,'role'=>'agent'],
        ]);
        $created = now()->subDays(2);
        $fake = Ticket::create([
            'code'=>'SD-000111', 'title'=>'Demo SLA correcto',
            'description'=>'Datos ficticios de ejemplo',
            'requester_id'=>$demo->id,'assignee_id'=>$demo->id,
            'department_id'=>$department->id,'ticket_category_id'=>$category->id,
            'priority'=>'normal','status'=>'resolved','is_portfolio_demo'=>true,
            'due_at'=>$created->copy()->addHours(5),
            'resolved_at'=>$created->copy()->addHours(3),
        ]);
        $fake->forceFill(['created_at'=>$created,'updated_at'=>$created])->save();
        $real = Ticket::create([
            'code'=>'SD-000112', 'title'=>'Privado que no debe filtrarse',
            'description'=>'Información reservada',
            'requester_id'=>$requester->id,'assignee_id'=>$agent->id,
            'department_id'=>$department->id,'ticket_category_id'=>$category->id,
            'priority'=>'critical','status'=>'open','is_portfolio_demo'=>false,
            'due_at'=>now()->subHour(),
        ]);
        return compact('demo','agent','requester','fake','real');
    }

    public function test_demo_report_metrics_only_include_fictional_ticket(): void
    {
        extract($this->fixture());
        $this->actingAs($demo)->get('/reports')->assertOk()
            ->assertSee('Reportes operativos')
            ->assertViewHas('total',1)
            ->assertViewHas('completedCount',1)
            ->assertViewHas('slaEligible',1)
            ->assertViewHas('slaMet',1)
            ->assertViewHas('overdue',0)
            ->assertDontSee('Privado que no debe filtrarse');
        $this->actingAs($agent)->get('/reports')->assertOk()
            ->assertViewHas('total',1)
            ->assertViewHas('completedCount',0)
            ->assertViewHas('overdue',1);
    }

    public function test_csv_download_excludes_operational_data_from_demo(): void
    {
        extract($this->fixture());
        $response = $this->actingAs($demo)->get('/reports/export');
        $response->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('"SLA cumplidos",1', $content);
        $this->assertStringNotContainsString('Privado que no debe filtrarse', $content);
        $this->assertStringNotContainsString('Solicitante privado', $content);
    }

    public function test_invalid_dates_are_rejected_and_requester_has_scoped_counts(): void
    {
        extract($this->fixture());
        $this->actingAs($demo)->get('/reports?from=2026-01-01&to=2028-01-01')
            ->assertSessionHasErrors();
        $this->actingAs($demo)->get('/reports?from=2026-11-22&to=2026-11-01')
            ->assertSessionHasErrors();
        $this->actingAs($requester)->get('/reports')->assertOk()->assertViewHas('total',1);
        $this->actingAs($requester)->get('/reports/export')->assertOk();
    }

    public function test_unauthenticated_visitors_cannot_access_reports_or_csv(): void
    {
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/reports/export')->assertRedirect('/login');
    }
}
