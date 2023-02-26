<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SystemInformation extends Model
{
    protected $table = "system_information";
    protected $fillable = ['id', 'email'];
    protected $casts = [
        'client_id' => 'array',
        'role' => 'array',
        'privileges' => 'array'
    ];
    protected $dates = ['created_at', 'updated_at'];
}
