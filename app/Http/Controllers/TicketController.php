<?php
namespace App\Http\Controllers;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Support\TicketAccess;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class TicketController extends Controller {
 public function index(Request $request):View {
  $user=$request->user();$deps=$user->departments()->pluck('departments.id');
  $filters=$request->validate(['status'=>['nullable',Rule::in(Ticket::STATUSES)],'priority'=>['nullable',Rule::in(Ticket::PRIORITIES)],'department_id'=>['nullable','integer'],'q'=>['nullable','string','max:100'],'mine'=>['nullable',Rule::in(['1'])]]);
  $query=Ticket::query()->with(['department:id,name','requester:id,name','assignee:id,name']);
  if ($user->is_portfolio_demo) $query->where('is_portfolio_demo',true);
  elseif (! $user->is_owner) $query->where('is_portfolio_demo',false);
  if(!$user->isAdmin())$query->where(function($q)use($user,$deps){$q->where('requester_id',$user->id);if($user->role==='agent')$q->orWhereIn('department_id',$deps);});
  if(!empty($filters['mine']))$query->where('requester_id',$user->id);
  if(!empty($filters['status']))$query->where('status',$filters['status']);
  if(!empty($filters['priority']))$query->where('priority',$filters['priority']);
  if(!empty($filters['department_id']))$query->where('department_id',(int)$filters['department_id']);
  if(!empty($filters['q'])){ $term=str_replace(['%','_'],['\\%','\\_'],$filters['q']);$query->where(fn($q)=>$q->where('title','like','%'.$term.'%')->orWhere('code','like','%'.$term.'%')); }
  $tickets=$query->orderByDesc('id')->paginate(20)->withQueryString();
  $departments=Department::where('active',true)->orderBy('name')->get();
  return view('tickets.index',compact('tickets','departments','filters'));
 }
 public function create():View {
  $departments=Department::where('active',true)->orderBy('name')->get();
  $categories=TicketCategory::where('active',true)->orderBy('name')->get();
  return view('tickets.create',compact('departments','categories'));
 }
 public function store(Request $request):RedirectResponse {
  $data=$request->validate(['title'=>['required','string','min:5','max:180'],'description'=>['required','string','min:10','max:10000'],'department_id'=>['required','integer','exists:departments,id'],'ticket_category_id'=>['required','integer','exists:ticket_categories,id'],'priority'=>['required',Rule::in(Ticket::PRIORITIES)]]);
  $category=TicketCategory::where('id',$data['ticket_category_id'])->where('department_id',$data['department_id'])->where('active',true)->firstOrFail();
  abort_unless(Department::whereKey($data['department_id'])->where('active',true)->exists(),422);
  // Initial SLA is elapsed hours, not business hours. This is explicitly labeled in UI.
  $ticket=DB::transaction(function()use($request,$data,$category){
   $t=Ticket::create(['title'=>$data['title'],'description'=>$data['description'],'requester_id'=>$request->user()->id,'department_id'=>$data['department_id'],'team_id'=>$category->team_id,'ticket_category_id'=>$category->id,'priority'=>$data['priority'],'status'=>'open','is_portfolio_demo'=>(bool)$request->user()->is_portfolio_demo,'due_at'=>now()->addHours($category->sla_hours)]);
   $t->update(['code'=>'SD-'.str_pad((string)$t->id,6,'0',STR_PAD_LEFT)]);
   $t->activities()->create(['user_id'=>$request->user()->id,'action'=>'created','details'=>['department_id'=>$t->department_id,'category_id'=>$category->id]]);
   return $t;
  },3);
  return redirect()->route('tickets.show',$ticket)->with('ok','Solicitud registrada.');
 }
 public function show(Request $request,Ticket $ticket):View {
  TicketAccess::view($request->user(),$ticket);
  $isAgent=TicketAccess::handles($request->user(),$ticket->department_id);
  $ticket->load(['requester:id,name,email','assignee:id,name','department:id,name','team:id,name','category:id,name',
    'messages'=>fn($q)=>$q->where(function($visible) use ($request,$ticket) {
      $visible->where('internal',false);
      if ($request->user()->isAdmin()) $visible->orWhere('internal',true);
      elseif (TicketAccess::handles($request->user(),$ticket->department_id)) $visible->orWhere(fn($internal)=>$internal->where('internal',true)->where('department_id',$ticket->department_id));
    }), 'messages.user:id,name',
    'activities.user:id,name']);
  $users=$isAgent?User::query()->where('active',true)->whereIn('id',DB::table('department_user')->where('department_id',$ticket->department_id)->pluck('user_id'))->orderBy('name')->get(['id','name']):collect();
  if($isAgent && $request->user()->is_portfolio_demo)$users=User::whereKey($request->user()->id)->get(['id','name']);
  if($isAgent && $request->user()->isAdmin())$users=User::where('active',true)->whereIn('role',['agent','admin'])->orderBy('name')->get(['id','name']);
  $teams=$isAgent?Team::where('department_id',$ticket->department_id)->where('active',true)->orderBy('name')->get(['id','name']):collect();
  $departments=$isAgent?Department::where('active',true)->orderBy('name')->get(['id','name']):collect();
  $categories=$isAgent?TicketCategory::where('active',true)->orderBy('name')->get(['id','name','department_id']):collect();
  return view('tickets.show',compact('ticket','isAgent','users','teams','departments','categories'));
 }
 public function message(Request $request,Ticket $ticket):RedirectResponse {
  TicketAccess::view($request->user(),$ticket);
  $data=$request->validate(['body'=>['required','string','min:2','max:10000'],'internal'=>['sometimes','boolean']]);
  $internal=(bool)($data['internal']??false);
  if($internal) TicketAccess::handle($request->user(),$ticket);
  abort_if($ticket->status==='closed',409,'El ticket está cerrado.');
  DB::transaction(function()use($request,$ticket,$data,$internal){
   $ticket->messages()->create(['user_id'=>$request->user()->id,'body'=>$data['body'],'internal'=>$internal,'department_id'=>$ticket->department_id]);
   $ticket->activities()->create(['user_id'=>$request->user()->id,'action'=>$internal?'internal_note':'reply']);
  });
  return back()->with('ok',$internal?'Nota interna agregada.':'Respuesta publicada.');
 }
 public function update(Request $request,Ticket $ticket):RedirectResponse {
  TicketAccess::handle($request->user(),$ticket);
  $data=$request->validate(['status'=>['required',Rule::in(Ticket::STATUSES)],'priority'=>['required',Rule::in(Ticket::PRIORITIES)],'team_id'=>['nullable','integer'],'assignee_id'=>['nullable','integer']]);
  DB::transaction(function()use($ticket,$request,$data){
   $locked=Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
   abort_if($locked->status==='closed',409,'Un ticket cerrado no puede modificarse.');
   $teamId=$data['team_id']??null;$assigneeId=$data['assignee_id']??null;
   if($teamId!==null)abort_unless(Team::where('id',$teamId)->where('department_id',$locked->department_id)->where('active',true)->exists(),422,'Equipo inválido.');
   if($assigneeId!==null){
    $assignee=User::whereKey($assigneeId)->where('active',true)->firstOrFail();
    abort_unless($assignee->isAgent() && ($assignee->isAdmin() || $assignee->departments()->whereKey($locked->department_id)->exists()),422,'Responsable fuera del departamento.');
    if ($request->user()->is_portfolio_demo) abort_unless($assignee->id === $request->user()->id && $assignee->is_portfolio_demo,422,'Solo el usuario ficticio puede recibir tickets de evaluación.');
    if (! $request->user()->is_portfolio_demo) abort_unless(! $assignee->is_portfolio_demo,422,'No se puede asignar un ticket operativo a una cuenta demo.');
    if($teamId!==null && !$assignee->isAdmin())abort_unless($assignee->departments()->whereKey($locked->department_id)->exists() && DB::table('team_user')->where('team_id',$teamId)->where('user_id',$assigneeId)->exists(),422,'El responsable no pertenece al equipo.');
   }
   $old=$locked->only(['status','priority','team_id','assignee_id']);
   if($data['status']==='resolved' && !$locked->resolved_at)$locked->resolved_at=now();
   if($data['status']==='closed'){$locked->closed_at=now();if(!$locked->resolved_at)$locked->resolved_at=now();}
   if($data['status']!=='resolved' && $data['status']!=='closed')$locked->resolved_at=null;
   $locked->fill(['status'=>$data['status'],'priority'=>$data['priority'],'team_id'=>$teamId,'assignee_id'=>$assigneeId]);$locked->save();
   $locked->activities()->create(['user_id'=>$request->user()->id,'action'=>'updated','details'=>['before'=>$old,'after'=>$locked->only(['status','priority','team_id','assignee_id'])]]);
  },3);
  return back()->with('ok','Ticket actualizado.');
 }

 public function transfer(Request $request, Ticket $ticket): RedirectResponse {
  TicketAccess::handle($request->user(), $ticket);
  $data=$request->validate([
   'department_id'=>['required','integer','exists:departments,id'],
   'ticket_category_id'=>['required','integer','exists:ticket_categories,id'],
   'reason'=>['required','string','min:10','max:1000'],
  ]);
  $destination=Department::query()->whereKey($data['department_id'])->where('active',true)->firstOrFail();
  $category=TicketCategory::query()->whereKey($data['ticket_category_id'])
   ->where('department_id',$destination->id)->where('active',true)->firstOrFail();
  DB::transaction(function () use ($request,$ticket,$destination,$category,$data): void {
   $locked=Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
   TicketAccess::handle($request->user(),$locked);
   abort_if($locked->status==='closed',409,'No se puede transferir un ticket cerrado.');
   $previous=[
    'department_id'=>$locked->department_id, 'team_id'=>$locked->team_id,
    'ticket_category_id'=>$locked->ticket_category_id,'assignee_id'=>$locked->assignee_id,
   ];
   $locked->fill([
    'department_id'=>$destination->id,'team_id'=>$category->team_id,
    'ticket_category_id'=>$category->id,'assignee_id'=>null,'status'=>'open',
    'due_at'=>now()->addHours($category->sla_hours),'resolved_at'=>null,'closed_at'=>null,
   ]);
   $locked->save();
   $locked->activities()->create([
    'user_id'=>$request->user()->id,'action'=>'transferred',
    'details'=>[
     'before'=>$previous,
     'after'=>['department_id'=>$destination->id,'team_id'=>$category->team_id,
               'ticket_category_id'=>$category->id,'assignee_id'=>null],
     'reason'=>$data['reason'],
    ],
   ]);
  },3);
  return redirect()->route('tickets.index')->with('ok','Solicitud transferida al departamento destino.');
 }
}
