<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeOffRequest extends Model
{
    protected $table = 'time_off_requests';
    protected $fillable = []; 
    protected $casts = [
        'is_without_pay' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];


    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    public function time_off_type()
    {
        return $this->hasOneDeep(
            'App\TimeOff',
            ['App\TimeOffBalance'],
            ['id', 'id'],
            ['time_off_balance_id', 'time_off_id']
        );
    }

    public function time_off_balance()
    {
        return $this->belongsTo('App\TimeOffBalance', 'time_off_balance_id');
    }

    public function requestor()
    {
        return $this->belongsTo('App\Employee', 'employee_id');
    }

    public function time_off_details()
    {
        return $this->hasMany('App\TimeOffDetails');
    }

    public function attachments()
    {
        return $this->hasMany('App\Document');
    }

    public function similar_requests()
    {
        return $this->hasMany('App\TimeOffRequest', 'time_off_type', 'time_off_type');
    }

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    public function approvers()
    {
        return $this->hasManyDeep(
            '\App\ApprovalItemEmployee',
            ['\App\ApprovalRequest', '\App\ApprovalLevel', '\App\ApprovalItem'],
            [
                'id',
                'approval_request_id',
                'approval_level_id',
                'approval_item_id',
            ],
            [
                'approval_request_id',
                'id',
                'id',
                'id',
            ]
        );
    }

    public function overtime_use()
    {
        return $this->morphToMany('App\OvertimeUse', 'overtime_user');
    }

    public function getStartDetailAttribute()
    {
        $result = null;
        $details = $this->time_off_details;
        foreach ($details as $detail) {
            if ($result == null || $detail->time_off_date < $result->time_off_date) {
                $result = $detail;
            }
        }
        return $result;
    }

    public function getEndDetailAttribute()
    {
        $result = null;
        $details = $this->time_off_details;
        foreach ($details as $detail) {
            if ($result == null || $detail->time_off_date > $result->time_off_date) {
                $result = $detail;
            }
        }
        return $result;
    }

    public function scopePendingOrApproved($query)
    {
        return $query->where('status', 0)->orWhere('status', 1);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 1);
    }

    public function scopeWithPay($query)
    {
        return $query->where('is_without_pay', false);
    }

    public function scopeWithoutPay($query)
    {
        return $query->where('is_without_pay', true);
    }

    public function scopeMonthUpdated($query, $month)
    {
        return $query->whereMonth('updated_at', $month);
    }

    public function scopeYearUpdated($query, $year)
    {
        return $query->whereYear('updated_at', $year);
    }

    public function scopeEffectiveYear($query, $year = null) {
        if ($year === null) {
            $year = Carbon::now()->format('y');
        }
        return $query->whereHas('time_off_details', function ($q) use ($year) {
            $q->whereRaw('YEAR(time_off_date)', $year);
        });
    }

    public function scopeSl($query)
    {
        return $query->whereHas('time_off_type', function ($q) {
            return $q->whereTimeOffCode('SL');
        });
    }

    public function scopeVlSl($query)
    {
        return $query->whereHas('time_off_type', function ($q) {
            return $q->whereTimeOffCode('SL');
        })->orWhereHas('time_off_type', function ($q) {
            return $q->whereTimeOffCode('VL');
        });
    }

    public function getInclusiveDates() {
        $start_date = $this->start_detail->time_off_date;
        $end_date = $this->end_detail->time_off_date;

        if ($start_date === $end_date) {
            return Carbon::createFromFormat('Y-m-d', $start_date)->format('F j Y');
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $start_date);
        $endDate = Carbon::createFromFormat('Y-m-d', $end_date);

        $inclusiveDates = $startDate->format('F j - ');

        if ($startDate->format('m') === $endDate->format('m')) {
            return $inclusiveDates . $endDate->format('j Y');
        } else {
            return $inclusiveDates . $endDate->format('F j Y');
        }
    }

    public function getTotalDaysStr() {
        if ($this->total_days == 1) {
            return '1 DAY';
        } else {
            return "$this->total_days DAYS";
        }
    }
}
