<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class EmployeeCase extends Model
{
    use HasFactory;

    protected $table = "employee_cases";
    protected $fillable = [
        'title', 
        'type', 
        'date_filed', 
        'status_effective_date', 
        'remarks', 
        'status',
        'case_id',
        'date_of_resolution'
    ];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];


    public function employee()
    {
        return $this->belongsTo('App\Employee');
    }

    public function documents()
    {
        return $this->hasMany('App\Document');
    }
}
