<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeType extends Model
{

    public const TEMPORARY = 6;
    public const COS = 2;
    public const JOB_ORDER = 5;

    protected $table = "employee_types";
    protected $fillable = ['id', 'employee_type_name', 'time_offs_ids'];
    protected $casts = [
        'is_active' => 'boolean',
        'time_offs_ids' => 'array'
    ];
    protected $dates = ['created_at', 'updated_at'];

    public function members() {
        return $this->hasMany('\App\EmploymentAndCompensation', 'employee_type_id');
    }

    public function time_offs() {
        return $this->belongsToMany('App\TimeOff', 'employee_type_time_off');
    }

    // caused error
    // public function time_offs () {
    //     return $this->belongsToMany('\App\TimeOff', 'employee_type_time_off');
    // }
}
