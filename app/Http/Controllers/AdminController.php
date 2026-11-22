<?php
namespace App\Http\Controllers;
use App\Models\Department;
use App\Models\Team;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class AdminController extends Controller {
 private function guard(Request $r):void{abort_unless($r->user()->isAdmin(),403);}
 public function index(Request $r):View {
  $this->guard($r);$departments=Department::with('teams','categories')->orderBy('name')->get();
  $users=User::where('active',true)->orderBy('name')->get();
  $demoTicketCount = $r->user()->is_owner ? \App\Models\Ticket::query()->where('is_portfolio_demo', true)->count() : null;
  return view('admin.index',compact('departments','users','demoTicketCount'));
 }
 public function department(Request $r):RedirectResponse {
  $this->guard($r);$data=$r->validate(['name'=>['required','string','max:120'],'code'=>['required','alpha_dash','max:30',Rule::unique('departments','code')]]);
  Department::create(['name'=>$data['name'],'code'=>strtoupper($data['code'])]);return back()->with('ok','Departamento registrado.');
 }
 public function team(Request $r):RedirectResponse {
  $this->guard($r);$data=$r->validate(['department_id'=>['required','exists:departments,id'],'name'=>['required','string','max:120'],'code'=>['required','alpha_dash','max:30']]);
  Team::create($data);return back()->with('ok','Equipo registrado.');
 }
 public function category(Request $r):RedirectResponse {
  $this->guard($r);$data=$r->validate(['department_id'=>['required','exists:departments,id'],'team_id'=>['nullable','exists:teams,id'],'name'=>['required','string','max:120'],'code'=>['required','alpha_dash','max:30'],'sla_hours'=>['required','integer','min:1','max:720']]);
  if(!empty($data['team_id']))abort_unless(Team::whereKey($data['team_id'])->where('department_id',$data['department_id'])->exists(),422);
  TicketCategory::create($data);return back()->with('ok','Categoría registrada.');
 }
 public function user(Request $r):RedirectResponse {
  $this->guard($r);$data=$r->validate(['name'=>['required','string','max:120'],'email'=>['required','email',Rule::unique('users','email')],'password'=>['required','string','min:16'],'role'=>['required',Rule::in(['requester','agent'])]]);
  User::create($data);return back()->with('ok','Usuario registrado.');
 }
 public function membership(Request $r):RedirectResponse {
  $this->guard($r);$data=$r->validate(['user_id'=>['required','exists:users,id'],'department_id'=>['required','exists:departments,id'],'role'=>['required',Rule::in(['agent','lead'])]]);
  $u=User::findOrFail($data['user_id']);abort_unless($u->isAgent(),422,'Primero crea un usuario agente.');
  DB::table('department_user')->updateOrInsert(['user_id'=>$u->id,'department_id'=>$data['department_id']],['role'=>$data['role']]);
  return back()->with('ok','Acceso al departamento actualizado.');
 }
 public function teamMember(Request $r):RedirectResponse {
  $this->guard($r);$data=$r->validate(['user_id'=>['required','exists:users,id'],'team_id'=>['required','exists:teams,id']]);
  $team=Team::findOrFail($data['team_id']);$user=User::findOrFail($data['user_id']);
  abort_unless($user->isAgent() && ($user->isAdmin() || $user->departments()->whereKey($team->department_id)->exists()),422,'El usuario no pertenece al departamento.');
  DB::table('team_user')->updateOrInsert(['team_id'=>$team->id,'user_id'=>$user->id],[]);
  return back()->with('ok','Usuario asignado al equipo.');
 }
}
