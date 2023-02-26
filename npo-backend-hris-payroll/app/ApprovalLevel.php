<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApprovalLevel extends Model
{
    protected $table = "approval_level";
    protected $fillable = ['description', 'approval_request_id', 'dependent_on', 'created_by'];
    protected $guarded = ['status'];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];


    public function items()
    {
        return $this->hasMany('App\ApprovalItem', 'approval_level_id');
    }
}
