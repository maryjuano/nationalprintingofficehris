<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Philhealth extends Model
{
    protected $table = 'philhealth';
    protected $fillable = [];
    protected $casts = [
        'government_share' => 'array',
        'monthly_premium' => 'array',
        'personal_share' => 'array',
    ];
    protected $dates = ['created_at', 'updated_at'];
}
