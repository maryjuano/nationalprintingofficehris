<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OvertimeUse extends Model
{
    protected $table = 'overtime_uses';
    protected $fillable = [ 'overtime_request_id', 'duration_in_minutes'];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];

    public function time_off_requests() {
        return $this->morphedByMany('App\TimeOffRequest', 'overtime_user');
    }
  
    public function payroll_run() {
        return $this->morphedByMany('App\PayrollRun', 'overtime_user');
    }
}
