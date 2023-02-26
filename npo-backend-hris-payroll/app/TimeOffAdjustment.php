<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeOffAdjustment extends Model
{
    protected $fillable = ['time_off_balance_id', 'adjustment_value', 'effectivity_date', 'remarks'];

    protected $casts = [
        'adjustment_value' => 'float'
    ];

    public function time_off_balance()
    {
        return $this->belongsTo('App\TimeOffBalance');
    }

    public function scopeWithinYear($query, $year = null) {
        if ($year === null) {
            $year = Carbon::now()->format('y');
        }
        // $q->whereRaw('YEAR(effectivity_date)', $year);
        return $query->whereYear('effectivity_date', $year);
    }
}
