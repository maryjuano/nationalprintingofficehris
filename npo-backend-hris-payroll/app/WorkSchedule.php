<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkSchedule extends Model
{
    protected $table = "work_schedules";
    protected $fillable = ['id', 'work_schedule_name', 'time_option', 'flexible_weekly_hours'];
    protected $casts = [
        'is_active' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];
    protected $appends = ['time_option_details'];

    public function breaks()
    {
        return $this->hasMany('App\BreakTime', 'work_schedule_id');
    }

    public function fixed_daily_hours()
    {
        return $this->hasOne('App\FixedDailyHours', 'work_schedule_id');
    }

    public function fixed_daily_times()
    {
        return $this->hasOne('App\FixedDailyTimes', 'work_schedule_id');
    }

    public function assigned_employees()
    {
        return $this->hasMany('App\EmploymentAndCompensation', 'work_schedule_id');
    }

    public function getTimeOptionDetailsAttribute () {
        if ($this->time_option == 1 && $this->fixed_daily_hours) {
            $daily_hours = $this->fixed_daily_hours->daily_hours;
            foreach ($daily_hours as $daily_hour => $details) {
                $time_option_details[$daily_hour] = $details;
            }
            return $time_option_details;
        } else if ($this->time_option == 2 && $this->fixed_daily_times) {
            $start_times = $this->fixed_daily_times->start_times;
            $end_times = $this->fixed_daily_times->end_times;
            $grace_periods = $this->fixed_daily_times->grace_periods;
            $keys = array_keys($grace_periods);
            foreach ($keys as $key => $value) {
                $time_option_details[$value] = array();
                $time_option_details[$value]['start_time'] = $start_times[$value];
                $time_option_details[$value]['end_time'] = $end_times[$value];
                $time_option_details[$value]['grace_period'] = $grace_periods[$value];
            }
            return $time_option_details;
        } else if ($this->time_option == 3) {
            return ['flexible_weekly_hours' => $this->flexible_weekly_hours];
        } else {
            return (object) array();
        }
    }
}
