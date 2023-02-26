<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeStubList extends Model
{
    protected $table = "list_employee_stub_view";
    protected $casts = [
        'earnings' => 'array',
        'deductions' => 'array',
        'contributions' => 'array',
        'loans' => 'array',
        'reimbursements' => 'array',
    ];

    public function latest_pagibig_request()
    {
        return $this->hasOne('\App\ContributionRequest', 'employee_id', 'employee_id')
            ->where([
                ['contribution_type', 'pagibig'],
                ['status', 1],
            ])
            ->orderBy('updated_at', 'desc');
    }

    public function loan_requests()
    {
        return $this->hasMany('\App\LoanRequest', 'employee_id', 'employee_id');
    }

    public function salary()
    {
        return $this->hasOne('\App\Salary', 'grade', 'salary_grade_id')->withDefault(['step' => []]);
    }

    public function dtrs()
    {
        return $this->hasMany('\App\Dtr', 'employee_id', 'employee_id');
    }

    public function employment_type()
    {
        return $this->hasOne('\App\EmploymentAndCompensation', 'employee_id', 'employee_id')
            ->select(
                'employee_id',
                'employee_type_id',
                'salary_rate'
            );
    }

    public function overtime_requests()
    {
        return $this->hasMany(\App\OvertimeRequest::class, 'employee_id', 'employee_id');
    }
}
