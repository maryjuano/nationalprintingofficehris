<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakTime extends Model
{
    protected $table = "break_times";
    protected $fillable = [
        'work_schedule_id',
        'name',
        'type',
        'period',
        'start_time',
        'end_time',
        'minutes',
        'status',
        'start_time_next_day',
        'end_time_next_day'
    ];
    protected $casts = [
        'start_time_next_day' => 'boolean',
        'end_time_next_day' => 'boolean'
    ];
}
