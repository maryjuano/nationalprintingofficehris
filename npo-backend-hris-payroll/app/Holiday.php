<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    protected $table = "holidays";
    protected $fillable = ['id', 'holiday_name', 'time_data_id', 'date', 'is_recurring'];
    protected $casts = [
        'status' => 'boolean',
        'is_recurring' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];


    public function time_data()
    {
        return $this->belongsTo('App\TimeData', 'time_data_id');
    }

    public static function get_time_data_if_holiday($date) {
        $holidays = \App\Holiday::where('is_recurring', True)
            ->orWhere('date', $date)
            ->get();
        if (!$holidays) {
            return null;
        }
        else {
            foreach ($holidays as $holiday) {
                if ($holiday->is_recurring) {
                    if (substr($holiday->date, 5) == substr($date, 5)) {
                        return $holiday->time_data;
                    }
                }
                else {
                    return $holiday->time_data;
                }
            }
        }
    }
}
