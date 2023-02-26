<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppFlowLevelEmployee extends Model
{
    protected $table = "app_flow_level_employee";
    protected $fillable = ['app_flow_levels_id', 'approver_id', 'can_approve'];
    protected $dates = ['created_at', 'updated_at'];
}
