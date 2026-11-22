<?php
namespace App\Support;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class TicketAccess {
 public static function handles(User $user,int $departmentId):bool {
  return $user->isAdmin() || ($user->role==='agent' && DB::table('department_user')->where('department_id',$departmentId)->where('user_id',$user->id)->exists());
 }
 public static function canView(User $user,Ticket $ticket):bool {
  return $ticket->requester_id===$user->id || self::handles($user,$ticket->department_id);
 }
 public static function view(User $user,Ticket $ticket):void {abort_unless(self::canView($user,$ticket),404);}
 public static function handle(User $user,Ticket $ticket):void {abort_unless(self::handles($user,$ticket->department_id),404);}
}
