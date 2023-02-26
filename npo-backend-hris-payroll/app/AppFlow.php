<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AppFlow extends Model
{
    protected $table = "app_flows";
    protected $fillable = ['department_id', 'section_id', 'request_type', 'name', 'pick_employee'];
    protected $casts = [
        'pick_employee' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];


    public function department()
    {
        return $this->belongsTo('App\Department');
    }

    public function requestors()
    {
        return $this->belongsToMany('App\Employee', 'app_flow_employee', 'app_flow_id', 'requestor_id')
            ->withTimestamps();
    }

    public function levels()
    {
        return $this->hasMany('App\AppFlowLevel', 'app_flow_id')->orderby('dependent_on');;
    }
}
