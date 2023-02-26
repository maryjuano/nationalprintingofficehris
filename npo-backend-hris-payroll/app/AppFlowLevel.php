<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AppFlowLevel extends Model
{
    protected $table = "app_flow_levels";
    protected $fillable = ['app_flow_id', 'description', 'dependent_on', 'selection_mode', 'created_by', 'updated_by'];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];


    public function approval_flow()
    {
        return $this->belongsTo('App\AppFlow', 'app_flow_id');
    }

    public function approvers()
    {
        return $this->belongsToMany('App\Employee', 'app_flow_level_employee', 'app_flow_levels_id', 'approver_id')
            ->withPivot('id', 'can_approve')->withTimestamps();
    }
}
