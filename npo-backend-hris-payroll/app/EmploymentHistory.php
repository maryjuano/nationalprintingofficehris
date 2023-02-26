<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmploymentHistory extends Model
{
    protected $table = "employment_history";
    protected $fillable = [
        'employee_id',
        'position_id',
        'department_id',
        'start_date',
        'end_date',
        'status',
        'salary',
        'tranche_version',
        'branch',
        'lwop',
        'separation_date',
        'separation_cause',
        'separation_amount_received',
        'remarks',
    ];
    protected $casts = [
        'attachments' => 'array'
    ];
    protected $appends = ['department_name', 'position_name', 'position_item_number'];
    protected $dates = ['created_at', 'updated_at'];



    public function position()
    {
        return $this->belongsTo('App\Position');
    }

    public function department()
    {
        return $this->belongsTo('App\Department');
    }

    public function getDepartmentNameAttribute()
    {
        return "NPO"; //$this->department->department_name ?? "";
    }

    public function getPositionNameAttribute()
    {
        return $this->position->position_name;
    }

    public function getPositionItemNumberAttribute()
    {
        return $this->position->item_number;
    }
}
