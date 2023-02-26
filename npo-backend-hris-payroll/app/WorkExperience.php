<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkExperience extends Model
{
    protected $table = "work_experience";
    protected $fillable = [
        'id',
        'employee_id',
        'company',
        'monthly_salary',
        'pay_grade',
        'status_of_appointment',
        'created_at',
        'updated_at',
        'start_inclusive_date',
        'end_inclusive_date',
        'government_service',
        'position_title'
    ];
    protected $casts = [
        'government_service' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];
}
