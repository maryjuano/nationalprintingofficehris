<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FixedDailyTimes extends Model
{
    protected $table = "fixed_daily_times";
    protected $fillable = ['id', 'work_schedule_id'];
    protected $casts = [
        'start_times' => 'array',
        'end_times' => 'array',
        'grace_periods' => 'array',
        'end_times_is_next_day' => 'array'
    ];
}
