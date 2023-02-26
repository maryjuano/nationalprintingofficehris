<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeStub extends Model
{
    protected $table = 'employee_stub';
    protected $fillable = ['employee_id'];
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [
        'earnings' => 'array',
        'deductions' => 'array',
        'contributions' => 'array',
        'loans' => 'array',
        'reimbursements' => 'array',
    ];
}
