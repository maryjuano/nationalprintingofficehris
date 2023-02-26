<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SalaryTranche extends Model
{
    protected $table = "salary_tranche";
    protected $fillable = ['effectivity_date'];
    protected $casts = [
        'is_active' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];


    public function salaries()
    {
        return $this->hasMany('App\Salary', 'salary_tranche_id');
    }
}
