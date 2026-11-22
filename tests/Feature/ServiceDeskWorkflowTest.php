<?php
namespace Tests\Feature;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class ServiceDeskWorkflowTest extends TestCase {
 use RefreshDatabase;
 private function fixture():array {
  $dept=Department::create(['code'=>'TI','name'=>'Tecnologías de información']);
  $category=TicketCategory::create(['department_id'=>$dept->id,'name'=>'Bases de datos','code'=>'DB','sla_hours'=>24]);
  $requester=User::create(['name'=>'Empleado','email'=>'employee@test.local','password'=>'SecurePass2026Strong!','role'=>'requester']);
  $agent=User::create(['name'=>'DBA','email'=>'dba@test.local','password'=>'SecurePass2026Strong!','role'=>'agent']);
  $outside=User::create(['name'=>'Externo','email'=>'external@test.local','password'=>'SecurePass2026Strong!','role'=>'agent']);
  DB::table('department_user')->insert(['department_id'=>$dept->id,'user_id'=>$agent->id,'role'=>'agent']);
  return compact('dept','category','requester','agent','outside');
 }
 public function test_requester_creates_ticket_agent_updates_and_internal_note_is_private():void {
  extract($this->fixture());
  $this->actingAs($requester)->post('/tickets',['title'=>'Falla de respaldo','description'=>'Se requiere diagnóstico del respaldo de base de datos.','department_id'=>$dept->id,'ticket_category_id'=>$category->id,'priority'=>'high'])->assertRedirect();
  $ticket=Ticket::firstOrFail();$this->assertSame('SD-'.str_pad((string)$ticket->id,6,'0',STR_PAD_LEFT),$ticket->code);
  $this->actingAs($agent)->put('/tickets/'.$ticket->id,['status'=>'in_progress','priority'=>'high','assignee_id'=>$agent->id])->assertRedirect();
  $this->actingAs($agent)->post('/tickets/'.$ticket->id.'/messages',['body'=>'Diagnóstico interno reservado','internal'=>1])->assertRedirect();
  $this->actingAs($requester)->get('/tickets/'.$ticket->id)->assertOk()->assertDontSee('Diagnóstico interno reservado');
  $this->actingAs($agent)->get('/tickets/'.$ticket->id)->assertOk()->assertSee('Diagnóstico interno reservado');
  $this->actingAs($outside)->get('/tickets/'.$ticket->id)->assertNotFound();
  $this->actingAs($outside)->put('/tickets/'.$ticket->id,['status'=>'closed','priority'=>'low'])->assertNotFound();
 }
 public function test_mismatched_category_rejected():void {
  extract($this->fixture());$other=Department::create(['code'=>'RH','name'=>'RH']);
  $this->actingAs($requester)->post('/tickets',['title'=>'Falla de respaldo','description'=>'El servicio no corresponde al departamento.','department_id'=>$other->id,'ticket_category_id'=>$category->id,'priority'=>'normal'])->assertNotFound();
  $this->assertSame(0,Ticket::count());
 }
 public function test_requester_cannot_add_internal_note():void {
  extract($this->fixture());
  $t=Ticket::create(['code'=>'SD-000009','title'=>'Solicitud','description'=>'Diagnóstico de incidencia','requester_id'=>$requester->id,'department_id'=>$dept->id,'ticket_category_id'=>$category->id]);
  $this->actingAs($requester)->post('/tickets/'.$t->id.'/messages',['body'=>'Nota secreta','internal'=>1])->assertNotFound();
  $this->assertDatabaseCount('ticket_messages',0);
 }
 public function test_agent_can_transfer_ticket_to_other_department():void {
  extract($this->fixture());
  $other=Department::create(['code'=>'RH','name'=>'Recursos Humanos']);
  $rhCategory=TicketCategory::create(['department_id'=>$other->id,'name'=>'Contratos','code'=>'CONTRACT','sla_hours'=>48]);
  $t=Ticket::create(['code'=>'SD-000045','title'=>'Solicitud alta','description'=>'Solicitar nueva cuenta de usuario','requester_id'=>$requester->id,'department_id'=>$dept->id,'ticket_category_id'=>$category->id]);
  $this->actingAs($agent)->post('/tickets/'.$t->id.'/transfer',['department_id'=>$other->id,'ticket_category_id'=>$rhCategory->id,'reason'=>'El trámite debe ser revisado por el equipo de Recursos Humanos.'])->assertRedirect('/tickets');
  $this->assertDatabaseHas('tickets',['id'=>$t->id,'department_id'=>$other->id,'status'=>'open','assignee_id'=>null]);
  $this->assertDatabaseHas('ticket_activities',['ticket_id'=>$t->id,'action'=>'transferred']);
  $this->actingAs($agent)->get('/tickets/'.$t->id)->assertNotFound();
  $this->actingAs($requester)->get('/tickets/'.$t->id)->assertOk();
 }

 public function test_transfer_keeps_prior_department_internal_notes_private():void {
  extract($this->fixture());
  $other=Department::create(['code'=>'RH','name'=>'Recursos Humanos']);
  $rhCategory=TicketCategory::create(['department_id'=>$other->id,'name'=>'Contratos','code'=>'CONTRACT','sla_hours'=>48]);
  $rhAgent=User::create(['name'=>'Agente RH','email'=>'rh@test.local','password'=>'SecurePass2026Strong!','role'=>'agent']);
  DB::table('department_user')->insert(['department_id'=>$other->id,'user_id'=>$rhAgent->id,'role'=>'agent']);
  $t=Ticket::create(['code'=>'SD-000046','title'=>'Revisión administrativa','description'=>'Revisión de autorización para alta','requester_id'=>$requester->id,'department_id'=>$dept->id,'ticket_category_id'=>$category->id]);
  $this->actingAs($agent)->post('/tickets/'.$t->id.'/messages',['body'=>'Contraseña temporal confidencial','internal'=>1])->assertRedirect();
  $this->actingAs($agent)->post('/tickets/'.$t->id.'/messages',['body'=>'El requerimiento pasa al área correspondiente'])->assertRedirect();
  $this->actingAs($agent)->post('/tickets/'.$t->id.'/transfer',['department_id'=>$other->id,'ticket_category_id'=>$rhCategory->id,'reason'=>'Se requiere autorización del área de Recursos Humanos.'])->assertRedirect('/tickets');
  $this->actingAs($rhAgent)->get('/tickets/'.$t->id)->assertOk()->assertSee('El requerimiento pasa al área correspondiente')->assertDontSee('Contraseña temporal confidencial');
  $this->actingAs($requester)->get('/tickets/'.$t->id)->assertOk()->assertDontSee('Contraseña temporal confidencial');
  $this->assertDatabaseHas('ticket_messages',['ticket_id'=>$t->id,'department_id'=>$dept->id,'internal'=>1]);
 }


 public function test_transferred_ticket_rejects_notes_and_updates_from_previous_department():void {
  extract($this->fixture());
  $other=Department::create(['code'=>'RH','name'=>'Recursos Humanos']);
  $rhCategory=TicketCategory::create([
   'department_id'=>$other->id,'name'=>'Contratos','code'=>'CONTRACT','sla_hours'=>48,
  ]);
  $ticket=Ticket::create([
   'title'=>'Revisión de solicitud','description'=>'Requiere autorización de otra área',
   'requester_id'=>$requester->id,'department_id'=>$dept->id,
   'ticket_category_id'=>$category->id,'priority'=>'normal','status'=>'open',
  ]);
  $this->actingAs($agent)->post('/tickets/'.$ticket->id.'/transfer',[
   'department_id'=>$other->id,
   'ticket_category_id'=>$rhCategory->id,
   'reason'=>'Esta solicitud corresponde ahora a Recursos Humanos.',
  ])->assertRedirect('/tickets');
  $this->actingAs($agent)->post('/tickets/'.$ticket->id.'/messages',[
   'body'=>'Nota sin acceso','internal'=>1,
  ])->assertNotFound();
  $this->actingAs($agent)->put('/tickets/'.$ticket->id,[
   'status'=>'resolved','priority'=>'high',
  ])->assertNotFound();
  $this->assertSame('open',$ticket->fresh()->status);
  $this->assertDatabaseMissing('ticket_messages',[
   'ticket_id'=>$ticket->id,'body'=>'Nota sin acceso',
  ]);
 }

 public function test_closed_ticket_refuses_new_reply_and_internal_note():void {
  extract($this->fixture());
  $ticket=Ticket::create([
   'title'=>'Caso cerrado','description'=>'Solicitud previamente resuelta',
   'requester_id'=>$requester->id,'department_id'=>$dept->id,
   'ticket_category_id'=>$category->id,'status'=>'closed','priority'=>'normal',
  ]);
  $this->actingAs($requester)->post('/tickets/'.$ticket->id.'/messages',[
   'body'=>'Respuesta posterior al cierre',
  ])->assertStatus(409);
  $this->actingAs($agent)->post('/tickets/'.$ticket->id.'/messages',[
   'body'=>'Nota posterior al cierre','internal'=>1,
  ])->assertStatus(409);
  $this->assertDatabaseCount('ticket_messages',0);
 }

}
