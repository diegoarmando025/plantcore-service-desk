<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TicketCategory extends Model {
 protected $fillable=['department_id','team_id','name','code','sla_hours','active'];
 protected function casts():array{return ['active'=>'boolean'];}
 public function department():BelongsTo{return $this->belongsTo(Department::class);}
 public function team():BelongsTo{return $this->belongsTo(Team::class);}
}
