<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payrun extends Model
{
    public const PAYRUN_STATUS_SIMULATED = 1;
    public const PAYRUN_STATUS_COMPLETED = 2;

    protected $table = "payroll_run";
    protected $fillable = [
        'payroll_name',
        'payroll_type',
        'payroll_period_start',
        'payroll_period_end',
        'deduction_start',
        'deduction_end',
        'other_inclusion',
        'employee_ids',
        'payroll_date',
        'run_type',
        'pay_structure',
        'days_in_month',
        'other_inclusion_1',
        'other_inclusion_2',
        'adjustments',
        'title',
        'subtitle',
        'bur_dv_description'
    ];
    protected $casts = [
        'pay_structure' => 'array',
        'employee_ids' => 'array',
        'other_inclusion' => 'array',
        'other_inclusion_1' => 'array',
        'other_inclusion_2' => 'array',
        'adjustments' => 'array',
        'subtitle' => 'array'
    ];
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function overtime_use() {
        return $this->morphToMany('App\OvertimeUse', 'overtime_user');
    }

    public function employee_logs()
    {
        return $this->hasMany('App\PayrollEmployeeLog', 'payroll_id');
    }
    public function createdBy()
    {
        return $this->hasOne('App\EmploymentAndCompensation', 'employee_id', 'created_by')
            ->select(
                'employment_and_compensation.employee_id',
                'employment_and_compensation.account_name'
            );
    }
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    public function positionName()
    { //https://laravel.com/docs/8.x/eloquent-relationships#has-one-through
        return $this->hasOneThrough(
            'App\Position',
            'App\EmploymentAndCompensation',
            'employee_id', // fk on EmpAndComp
            'id', // fk on Position
            'created_by', // payrun
            'position_id' // EmpAndComp
        );
    }
    public function getAdjustmentsObjAttribute()
    {
        if ($this->adjustments) {
            return Adjustment::whereIn('id', $this->adjustments)->get();
        }
        else {
            return [];
        }
    }
}
