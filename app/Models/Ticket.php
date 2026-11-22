<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Ticket extends Model {
 public const STATUSES=['open','assigned','in_progress','waiting','resolved','closed'];
 public const PRIORITIES=['low','normal','high','critical'];
 public const STATUS_LABELS=['open'=>'Abierta','assigned'=>'Asignada','in_progress'=>'En proceso','waiting'=>'En espera','resolved'=>'Resuelta','closed'=>'Cerrada'];
 public const PRIORITY_LABELS=['low'=>'Baja','normal'=>'Normal','high'=>'Alta','critical'=>'Crítica'];
 public function statusLabel():string{return self::STATUS_LABELS[$this->status]??$this->status;}
 public function priorityLabel():string{return self::PRIORITY_LABELS[$this->priority]??$this->priority;}
 public function statusBadgeClass():string{return match($this->status){'open'=>'text-bg-primary','assigned'=>'text-bg-info','in_progress'=>'text-bg-warning','waiting'=>'text-bg-secondary','resolved'=>'text-bg-success','closed'=>'text-bg-dark',default=>'text-bg-secondary'};}
 public function priorityBadgeClass():string{return match($this->priority){'critical'=>'text-bg-danger','high'=>'text-bg-warning','normal'=>'text-bg-primary','low'=>'text-bg-secondary',default=>'text-bg-secondary'};}
 protected $fillable=['code','title','description','requester_id','department_id','team_id','ticket_category_id','assignee_id','priority','status','due_at','resolved_at','closed_at','is_portfolio_demo'];
 protected function casts():array{return ['due_at'=>'datetime','resolved_at'=>'datetime','closed_at'=>'datetime','is_portfolio_demo'=>'boolean'];}
 public function requester():BelongsTo{return $this->belongsTo(User::class,'requester_id');}
 public function assignee():BelongsTo{return $this->belongsTo(User::class,'assignee_id');}
 public function department():BelongsTo{return $this->belongsTo(Department::class);}
 public function team():BelongsTo{return $this->belongsTo(Team::class);}
 public function category():BelongsTo{return $this->belongsTo(TicketCategory::class,'ticket_category_id');}
 public function attachments():HasMany{return $this->hasMany(TicketAttachment::class);}
 public function messages():HasMany{return $this->hasMany(TicketMessage::class);}
 public function activities():HasMany{return $this->hasMany(TicketActivity::class);}
}
