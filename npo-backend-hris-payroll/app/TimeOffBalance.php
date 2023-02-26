<?php

namespace App;

use App\Helpers\DayFractions;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DateTime;

class TimeOffBalance extends Model
{
    protected $table = "time_off_balance";
    protected $fillable = [
        'id',
        'employee_id',
        'time_off_id',
    ];
    protected $dates = ['created_at', 'updated_at'];
    protected $appends = ['total_used', 'total_adjustments', 'balance'];

    public function time_off()
    {
        return $this->belongsTo('App\TimeOff', 'time_off_id');
    }

    public function requests()
    {
        return $this->hasMany('App\TimeOffRequest', 'time_off_balance_id');
    }

    public function adjustments()
    {
        return $this->hasMany('App\TimeOffAdjustment');
    }

    public function getTotalUsed(DateTime $onOrBefore = null)
    {
        return $this->requests()->approved()->withPay()->when($onOrBefore, function ($query) use ($onOrBefore) {
            // return $query->where('updated_at', '<=', $onOrBefore);
            return $query->whereHas('time_off_details', function ($q) use ($onOrBefore) {
                $q->whereRaw("YEAR(time_off_date) = '" . $onOrBefore->format('Y') . "'");
                $q->where('time_off_date', '<=', $onOrBefore->format('Y-m-d'));
            });
        })->get()->reduce(function ($carry, $item) {
            return bcadd($carry, $item->total_days, 3);
        }, 0.00);
    }

    public function getTotalAdjustments(DateTime $onOrBefore = null)
    {
        return $this->adjustments()->when($onOrBefore, function ($query) use ($onOrBefore) {
            $query->whereRaw("YEAR(effectivity_date) = '" . $onOrBefore->format('Y') . "'");
            $query->where('effectivity_date', '<=', $onOrBefore->format('Y-m-d'));
        })->get()->reduce(function ($carry, $item) {
            return bcadd($carry, $item->adjustment_value, DayFractions::SCALE);
        }, 0);
    }

    public function getBalance(DateTime $onOrBefore = null)
    {
        return bcsub($this->getTotalAdjustments($onOrBefore), $this->getTotalUsed($onOrBefore), DayFractions::SCALE);
    }

    public function getTotalUsedAttribute()
    {
        return $this->getTotalUsed();
    }

    public function getTotalAdjustmentsAttribute()
    {
        return $this->getTotalAdjustments();
    }

    public function getBalanceAttribute()
    {
        return $this->getBalance();
    }

    public function scopeVl($query)
    {
        return $query->whereHas('time_off', function ($q) {
            return $q->whereTimeOffCode('VL');
        });
    }

    public function scopeSl($query)
    {
        return $query->whereHas('time_off', function ($q) {
            return $q->whereTimeOffCode('SL');
        });
    }
}
