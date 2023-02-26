<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Salary extends Model
{
    protected $table = "salaries";
    protected $fillable = ['salary_tranche_id', 'grade', 'step', 'updated_by', 'created_by'];
    protected $casts = [
        'step' => 'array',
        'status' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];


    public function salary_tranche()
    {
        return $this->belongsTo('App\SalaryTranche', 'salary_tranche_id');
    }
}
