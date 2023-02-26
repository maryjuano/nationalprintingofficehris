<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FixedDailyHours extends Model
{
    protected $table = "fixed_daily_hours";
    protected $fillable = ['id', 'work_schedule_id'];
    protected $casts = [
        'daily_hours' => 'array'
    ];
}
