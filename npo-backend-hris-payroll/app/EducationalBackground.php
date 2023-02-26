<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EducationalBackground extends Model
{
    protected $table = 'educational_background';
    protected $fillable = [
        'id',
        'employee_id',
        'type',
        'school_name',
        'course',
        'units_earned',
        'highest_level',
        'year_graduated',
        'honors',
        'created_at',
        'updated_at',
        'start_year',
        'start_month',
        'start_day',
        'end_year',
        'end_month',
        'end_day'
    ];
    protected $casts = [];

    protected $dates = ['created_at', 'updated_at'];
}
