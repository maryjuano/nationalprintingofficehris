<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    protected $table = "users";
    protected $fillable = [
        'name', 'email', 'permissions', 'id', 'division', 'section'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'permissions' => 'object'
    ];

    protected $appends = [
        'status'
    ];

    public function getStatusAttribute()
    {
        return $this->is_disabled ? 'Disabled' : 'Active';
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function employee_details()
    {
        return $this->hasOne('App\Employee', 'users_id');
    }
}
