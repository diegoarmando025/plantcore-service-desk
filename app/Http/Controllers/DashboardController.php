<?php
namespace App\Http\Controllers;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;
class DashboardController extends Controller {
 public function __invoke(Request $request):View {
  $user=$request->user();
  $departments=$user->departments()->pluck('departments.id');
  $query=Ticket::query()->when(!$user->isAdmin(),function($q)use($user,$departments){
    $q->where(function($q)use($user,$departments){$q->where('requester_id',$user->id);if($user->role==='agent')$q->orWhereIn('department_id',$departments);});
  });
  $counts=['total'=>(clone $query)->count(), 'open'=>(clone $query)->whereIn('status',['open','assigned','in_progress'])->count(), 'waiting'=>(clone $query)->where('status','waiting')->count(), 'overdue'=>(clone $query)->whereNotIn('status',['resolved','closed'])->where('due_at','<',now())->count(), 'resolved'=>(clone $query)->whereIn('status',['resolved','closed'])->count()];
  $recent=(clone $query)->with(['department:id,name','assignee:id,name'])->orderByDesc('id')->limit(10)->get();
  return view('dashboard',compact('counts','recent'));
 }
}
