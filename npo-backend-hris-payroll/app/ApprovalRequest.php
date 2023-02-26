<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApprovalRequest extends Model
{
    protected $table = "approval_requests";
    protected $fillable = ['request_type', 'requestor_id', 'created_at', 'updated_at', 'app_flow_id'];
    protected $guarded = ['status'];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];


    public function levels()
    {
        return $this->hasMany('App\ApprovalLevel', 'approval_request_id');
    }

    public function items()
    {
        return $this->hasManyThrough('App\ApprovalItem', 'App\ApprovalLevel', 'approval_request_id', 'approval_level_id');
    }
}
