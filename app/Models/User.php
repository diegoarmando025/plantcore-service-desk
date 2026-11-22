<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class User extends Authenticatable {
 use Notifiable;
 protected $fillable=['name','email','password','role','active'];
 protected $hidden=['password','remember_token'];
 protected function casts():array{return ['password'=>'hashed','active'=>'boolean','is_owner'=>'boolean','email_verified_at'=>'datetime'];}
 public function departments():BelongsToMany{return $this->belongsToMany(Department::class)->withPivot('role');}
 public function tickets():HasMany{return $this->hasMany(Ticket::class,'requester_id');}
 protected static function booted(): void {
  static::deleting(function (self $user): void {
   if ($user->is_owner) throw new \LogicException('No se puede eliminar la cuenta propietaria.');
  });
 }
 public function isAdmin():bool{return $this->role==='admin';}
 public function isAgent():bool{return in_array($this->role,['admin','agent'],true);}
}
