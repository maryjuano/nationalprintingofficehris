<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmploymentAndCompensation extends Model
{
    protected $table = 'employment_and_compensation';
    protected $fillable = [
        'id',
        'employee_id',
        'position_id',
        'department_id',
        'section_id',
        'employee_type_id',
        'salary_grade_id',
        'step_increment',
        'schedule_id',
        'account_name',
        'account_number',
        'sss_number',
        'pagibig_number',
        'gsis_number',
        'philhealth_number',
        'tin',
        'direct_report_id',
        'work_schedule_id',
        'work_sched_effectivity_date'
    ];
    protected $dates = [];

    public function employee_number()
    {
        return $this->hasOne('App\EmployeeIdNumber', 'employee_id', 'employee_id');
    }

    public function getIdNumberAttribute()
    {
        return $this->employee_number ? $this->employee_number->id_number : null;
    }

    public function direct_report()
    {
        return $this->belongsTo('App\Employee', 'direct_report_id');
    }

    public function department()
    {
        return $this->belongsTo('App\Department', 'department_id');
    }

    public function section()
    {
        return $this->belongsTo('App\Section', 'section_id');
    }

    public function salary()
    {
        return $this->hasOne('\App\Salary', 'grade', 'salary_grade_id')->withDefault(['step' => []]);
    }

    public function position()
    {
        return $this->belongsTo('App\Position', 'position_id');
    }

    public function employee_type()
    {
        return $this->belongsTo('App\EmployeeType', 'employee_type_id');
    }

    public function work_schedule()
    {
        return $this->belongsTo('App\WorkSchedule', 'work_schedule_id');
    }
}
