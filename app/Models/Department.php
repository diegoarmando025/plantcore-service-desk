<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Department extends Model {
 protected $fillable=['name','code','description','active'];
 protected function casts():array{return ['active'=>'boolean'];}
 public function teams():HasMany{return $this->hasMany(Team::class);}
 public function categories():HasMany{return $this->hasMany(TicketCategory::class);}
 public function tickets():HasMany{return $this->hasMany(Ticket::class);}
 public function users():BelongsToMany{return $this->belongsToMany(User::class)->withPivot('role');}
}
