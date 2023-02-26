<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StatutoryPhilhealth extends Model
{
    protected $table = 'philhealth';
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = [
        'id', 'minimum_range', 'maximum_range', 'personal_share', 'government_share', 'monthly_premium'
    ];

    protected $casts = [
        "personal_share" => "array",
        "government_share" => "array",
        "monthly_premium" => "array"
    ];
}
