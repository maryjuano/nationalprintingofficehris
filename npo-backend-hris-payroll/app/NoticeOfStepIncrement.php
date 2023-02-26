<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeOfStepIncrement extends Model
{
    protected $table = "notice_of_step_increment";
    protected $fillable = [
        'id',
        'employee_id', // Employee
        'generated_date',
        'effectivity_date',
        'old_rate',
        'new_rate',
        'old_step',
        'new_step',
        'grade', // TODO: may grade sa EmploymentAndCompensation tapos may grade din sa Position?
        'position_id', // Position
        'created_at',
        'updated_at',
        'remarks'
    ];
    protected $dates = ['created_at', 'updated_at', 'generated_date', 'effectivity_date'];
    protected $with = ['position', 'employee'];

    // relationships
    public function position()
    {
        return $this->belongsTo('App\Position', 'position_id');
    }

    // relationships
    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id');
    }
}
