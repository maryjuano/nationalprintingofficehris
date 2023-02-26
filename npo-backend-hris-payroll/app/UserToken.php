<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    protected $table = 'user_tokens';
    protected $fillable = [];
    protected $hidden = [];
    protected $casts = [
        'permissions' => 'array'
    ];
    protected $appends = [];
}
