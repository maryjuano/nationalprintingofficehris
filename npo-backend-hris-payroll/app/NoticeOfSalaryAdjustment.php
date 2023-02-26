<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeOfSalaryAdjustment extends Model
{
    use HasFactory;
    protected $table = "notice_of_salary_adjustment";
    protected $fillable = [
        'id',
        'employee_id', // Employee
        'generated_date',
        'effectivity_date',
        'old_rate',
        'new_rate',
        'old_step',
        'new_step',
        'old_grade',
        'new_grade',
        'old_position_id',
        'new_position_id',
        'created_at',
        'updated_at',
        'remarks'
    ];
    protected $dates = ['created_at', 'updated_at', 'generated_date', 'effectivity_date', 'previous_effectivity_date'];
    protected $with = ['old_position', 'new_position', 'employee'];

    // relationships
    public function old_position()
    {
        return $this->belongsTo('App\Position', 'old_position_id');
    }

    public function new_position()
    {
        return $this->belongsTo('App\Position', 'new_position_id');
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id');
    }

    public function getPreviousEffectivityDateAttribute()
    {
        return $this->effectivity_date->subDays(1);
    }
}
