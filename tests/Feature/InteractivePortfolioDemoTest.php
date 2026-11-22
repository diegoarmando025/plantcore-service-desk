<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Support\PortfolioDemoScenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InteractivePortfolioDemoTest extends TestCase {
    use RefreshDatabase;

    private function fixture(): array {
        $departments = [];
        foreach (['TI','RH','FIN','COM','MTTO'] as $code) {
            $d=Department::create(['code'=>$code,'name'=>$code]);
            $departments[$code]=$d;
        }
        foreach ([
            ['TI','DATABASE'],['TI','NETWORK'],['TI','APPLICATION'],
            ['RH','CONTRACT'],['FIN','INVOICE'],['COM','REQUISITION'],['MTTO','REPAIR']
        ] as [$department,$code]) {
            TicketCategory::create([
                'department_id'=>$departments[$department]->id,'name'=>$code,
                'code'=>$code,'sla_hours'=>24,
            ]);
        }
        $owner=User::create(['name'=>'Owner','email'=>'owner@example.invalid',
            'password'=>'A-Strong-Private-Password!','role'=>'admin']);
        $owner->forceFill(['is_owner'=>true])->save();
        $demo=User::create(['name'=>'Portfolio','email'=>'portfolio.demo@service-desk.example.invalid',
            'password'=>Str::random(48),'role'=>'agent']);
        $demo->forceFill(['is_portfolio_demo'=>true])->save();
        foreach ($departments as $d) {
            DB::table('department_user')->insert(['user_id'=>$demo->id,
                'department_id'=>$d->id,'role'=>'agent']);
        }
        PortfolioDemoScenario::seed($demo);
        return compact('owner','demo','departments');
    }

    public function test_demo_has_five_isolated_agents_per_department_and_realistic_workload(): void {
        extract($this->fixture());
        foreach (['TI', 'RH', 'FIN', 'COM', 'MTTO'] as $code) {
            $agents = User::where('is_portfolio_demo', true)
                ->where('role', 'agent')
                ->where('email', 'like', 'portfolio.'.strtolower($code).'%@service-desk.example.invalid')
                ->get();
            $this->assertCount(5, $agents, $code);
            foreach ($agents as $agent) {
                $this->assertFalse($agent->is_owner);
                $this->assertTrue($agent->departments()->whereKey($departments[$code]->id)->exists());
            }
        }
        $this->assertSame(25, Ticket::where('is_portfolio_demo', true)->count());
        $this->assertGreaterThan(0, Ticket::where('is_portfolio_demo', true)->where('due_at', '<', now())->whereNotIn('status', ['resolved','closed'])->count());
        $this->assertGreaterThan(0, \App\Models\TicketMessage::where('internal', true)->count());
    }

    public function test_guest_enters_demo_without_password_and_visits_real_modules(): void {
        extract($this->fixture());
        $this->post('/demo/entrar')->assertRedirect('/guia')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($demo);
        $this->get('/guia')->assertOk()->assertSee('Demo interactiva');
        $this->get('/dashboard')->assertOk();
        $this->get('/tickets')->assertOk();
        $this->get('/tickets/create')->assertOk();
        $this->get('/admin')->assertForbidden();
        $this->assertSame(25,Ticket::where('is_portfolio_demo',true)->count());
    }

    public function test_demo_can_create_update_reply_and_transfer_only_fictional_tickets(): void {
        extract($this->fixture());
        $this->actingAs($demo);
        $category=TicketCategory::where('department_id',$departments['TI']->id)->firstOrFail();
        $this->post('/tickets',[
            'title'=>'Demo nueva solicitud de base de datos',
            'description'=>'Revisar tiempos de respuesta del reporte de operaciones de ejemplo.',
            'department_id'=>$departments['TI']->id,
            'ticket_category_id'=>$category->id,'priority'=>'high',
        ])->assertRedirect();
        $created=Ticket::latest('id')->firstOrFail();
        $this->assertTrue($created->is_portfolio_demo);
        $this->put("/tickets/$created->id",[
            'status'=>'in_progress','priority'=>'high','assignee_id'=>$demo->id,
        ])->assertRedirect();
        $this->post("/tickets/$created->id/messages",[
            'body'=>'Revisando los índices de consulta.',
            'internal'=>1,
        ])->assertRedirect();
        $rhCategory=TicketCategory::where('department_id',$departments['RH']->id)->firstOrFail();
        $this->post("/tickets/$created->id/transfer",[
            'department_id'=>$departments['RH']->id,
            'ticket_category_id'=>$rhCategory->id,
            'reason'=>'El ejemplo requiere atención del equipo de recursos humanos.',
        ])->assertRedirect('/tickets');
        $this->assertDatabaseHas('tickets',[
            'id'=>$created->id,'is_portfolio_demo'=>true,
            'department_id'=>$departments['RH']->id,
        ]);
    }

    public function test_demo_can_assign_a_ticket_to_fictional_department_agent(): void {
        extract($this->fixture());
        $agent = User::where('email', 'portfolio.ti@service-desk.example.invalid')->firstOrFail();
        $ticket = Ticket::where('is_portfolio_demo',true)
            ->where('department_id', $departments['TI']->id)->firstOrFail();
        $this->actingAs($demo)->get("/tickets/$ticket->id")
            ->assertOk()->assertSee('Soporte de TI');
        $this->actingAs($demo)->put("/tickets/$ticket->id",[
            'status'=>'in_progress', 'priority'=>'high',
            'assignee_id'=>$agent->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id'=>$ticket->id, 'assignee_id'=>$agent->id, 'is_portfolio_demo'=>true,
        ]);
        $this->assertTrue($agent->is_portfolio_demo);
        $this->assertFalse($agent->is_owner);
    }

    public function test_demo_cannot_read_or_modify_real_ticket_or_manage_users(): void {
        extract($this->fixture());
        $category=TicketCategory::where('department_id',$departments['TI']->id)->firstOrFail();
        $real=Ticket::create([
            'title'=>'Private HR ticket','description'=>'Confidential operational record',
            'requester_id'=>$owner->id,'department_id'=>$departments['TI']->id,
            'ticket_category_id'=>$category->id,'status'=>'open','priority'=>'normal',
        ]);
        $this->actingAs($demo)->get("/tickets/$real->id")->assertNotFound();
        $this->actingAs($demo)->post("/tickets/$real->id/messages",[
            'body'=>'Unauthorized message',
        ])->assertNotFound();
        $this->actingAs($demo)->put("/tickets/$real->id",[
            'status'=>'closed','priority'=>'normal',
        ])->assertNotFound();
        $this->actingAs($demo)->get('/tickets')->assertDontSee('Private HR ticket');
        $this->actingAs($demo)->get('/dashboard')->assertDontSee('Private HR ticket');
        $this->actingAs($demo)->post('/admin/users',[
            'name'=>'Escalated','email'=>'escalated@example.invalid',
            'password'=>'Password-Long-Enough-2026','role'=>'agent',
        ])->assertForbidden();
    }

    public function test_regular_agent_cannot_access_demo_and_demo_assignment_is_restricted(): void {
        extract($this->fixture());
        $agent=User::create(['name'=>'Agent','email'=>'agent@example.invalid',
            'password'=>'A-Strong-Private-Password!','role'=>'agent']);
        DB::table('department_user')->insert([
            'department_id'=>$departments['TI']->id,'user_id'=>$agent->id,'role'=>'agent',
        ]);
        $ticket=Ticket::where('is_portfolio_demo',true)->where('department_id',$departments['TI']->id)->firstOrFail();
        $this->actingAs($agent)->get("/tickets/$ticket->id")->assertNotFound();
        $this->actingAs($agent)->get('/tickets')->assertDontSee($ticket->title);
        $this->actingAs($demo)->put("/tickets/$ticket->id",[
            'status'=>'in_progress','priority'=>'normal','assignee_id'=>$agent->id,
        ])->assertUnprocessable();
    }

    public function test_reset_removes_only_flagged_tickets_and_reseeds_examples(): void {
        extract($this->fixture());
        $category=TicketCategory::where('department_id',$departments['TI']->id)->firstOrFail();
        $real=Ticket::create([
            'title'=>'Real request','description'=>'Real operational data',
            'requester_id'=>$owner->id,'department_id'=>$departments['TI']->id,
            'ticket_category_id'=>$category->id,
        ]);
        $extra=Ticket::create([
            'title'=>'Extra fictitious request','description'=>'Created by visitor',
            'requester_id'=>$demo->id,'department_id'=>$departments['TI']->id,
            'ticket_category_id'=>$category->id,'is_portfolio_demo'=>true,
        ]);
        $deleted=PortfolioDemoScenario::reset($demo);
        $this->assertSame(26,$deleted);
        $this->assertDatabaseHas('tickets',['id'=>$real->id,'is_portfolio_demo'=>false]);
        $this->assertDatabaseMissing('tickets',['id'=>$extra->id]);
        $this->assertSame(25,Ticket::where('is_portfolio_demo',true)->count());
    }

    public function test_guest_access_refuses_unprovisioned_or_unsafe_account(): void {
        $this->post('/demo/entrar')->assertStatus(503);
        extract($this->fixture());
        DB::table('department_user')->where('user_id',$demo->id)->limit(1)->delete();
        $this->post('/demo/entrar')->assertStatus(503);
        $this->assertGuest();
    }
}
