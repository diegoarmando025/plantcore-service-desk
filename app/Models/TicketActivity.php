<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TicketActivity extends Model {
 protected $fillable=['ticket_id','user_id','action','details'];
 protected function casts():array{return ['details'=>'array'];}
 public function user():BelongsTo{return $this->belongsTo(User::class);}
}
