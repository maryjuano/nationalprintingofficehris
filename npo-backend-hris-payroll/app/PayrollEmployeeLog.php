<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PayrollEmployeeLog extends Model
{
    protected $table = 'payroll_history_for_all_employee';
    protected $fillable = [];
    protected $casts = [
        'inclusion_type' => 'array',
        'amount' => 'float'
    ];
    protected $dates = ['created_at', 'updated_at'];


    public function payrun()
    {
        return $this->belongsTo('App\Payrun', 'payroll_id');
    }
}
