<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApprovalItemEmployee extends Model
{
    protected $table = "approval_item_employee";
    protected $fillable = ['approval_item_id', 'approver_id', 'can_approve'];
    protected $casts = [
        'attachments' => 'array'
    ];
    protected $dates = ['created_at', 'updated_at'];
    protected $appends = ['approver_name'];


    public function approver()
    {
        return $this->belongsTo('App\Employee', 'approver_id');
    }

    public function getApproverNameAttribute()
    {
        return $this->approver->personal_information->last_name . ', ' .
            $this->approver->personal_information->first_name . ' ' .
            $this->approver->personal_information->middle_name;
    }

    public function approval_item()
    {
        return $this->belongsTo('App\ApprovalItem', 'approval_item_id');
    }

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    public function approval_level()
    {
        return $this->hasOneDeep(
            'App\ApprovalLevel',
            ['App\ApprovalItem'],
            ['id', 'id'],
            ['approval_item_id', 'approval_level_id']
        );
    }
}
