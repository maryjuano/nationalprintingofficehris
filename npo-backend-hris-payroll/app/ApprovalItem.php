<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApprovalItem extends Model
{
    protected $table = "approval_items";
    protected $fillable = ['created_at', 'approval_level_id'];
    protected $guarded = ['status'];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];


    public function approvers()
    {
        return $this->hasMany('App\ApprovalItemEmployee', 'approval_item_id');
    }

    public function approval_level()
    {
        return $this->belongsTo('App\ApprovalLevel', 'approval_level_id');
    }
}
