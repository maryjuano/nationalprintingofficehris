<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PLSRequest extends Model
{
    protected $table = 'pls_request';
    protected $fillable = [];
    protected $casts = [
        'status' => 'boolean'
    ];

    protected $dates = ['created_at', 'updated_at'];
}
