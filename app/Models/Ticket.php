<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Ticket extends Model {
 public const STATUSES=['open','assigned','in_progress','waiting','resolved','closed'];
 public const PRIORITIES=['low','normal','high','critical'];
 protected $fillable=['code','title','description','requester_id','department_id','team_id','ticket_category_id','assignee_id','priority','status','due_at','resolved_at','closed_at'];
 protected function casts():array{return ['due_at'=>'datetime','resolved_at'=>'datetime','closed_at'=>'datetime'];}
 public function requester():BelongsTo{return $this->belongsTo(User::class,'requester_id');}
 public function assignee():BelongsTo{return $this->belongsTo(User::class,'assignee_id');}
 public function department():BelongsTo{return $this->belongsTo(Department::class);}
 public function team():BelongsTo{return $this->belongsTo(Team::class);}
 public function category():BelongsTo{return $this->belongsTo(TicketCategory::class,'ticket_category_id');}
 public function messages():HasMany{return $this->hasMany(TicketMessage::class);}
 public function activities():HasMany{return $this->hasMany(TicketActivity::class);}
}
