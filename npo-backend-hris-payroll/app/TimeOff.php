<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeOff extends Model
{
    public const TYPE_CTO = 5;

    protected $table = 'time_offs';
    protected $fillable = [
        'time_off_type',
        'time_off_code',
        'default_balance',
        'unit',
        'time_off_color_id',
        'use_csl_matrix',
        'cash_convertible',
        'monthly_credit_balance',
        'monthly_credit_date',
        'annual_credit_reset_month',
        'annual_credit_reset_day',
        'can_carry_over',
        'minimum_used_credits'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'use_csl_matrix' => 'boolean',
        'cash_convertible' => 'boolean',
        'can_carry_over' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];

    public function requests()
    {
        return $this->hasMany('App\TimeOffBalance', 'time_off_id');
    }

    public function color()
    {
        return $this->belongsTo('App\TimeOffColor', 'time_off_color_id');
    }
}
