<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class AuthorityToOt extends Model
{
    protected $table = 'authority_to_ots';
    protected $fillable = ['remarks', 'start_date', 'end_date', 'requested_by'];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];

    public function overtime_requests()
    {
        return $this->hasMany('App\OvertimeRequest', 'authority_to_ot_id');
    }
}
