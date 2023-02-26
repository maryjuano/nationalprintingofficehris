<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class OvertimeRequest extends Model
{
    public const STATUS_UPCOMING = -3;
    public const STATUS_READY = -2;
    public const STATUS_PENDING = 0;
    public const STATUS_DECLINED = -1;
    public const STATUS_APPROVED = 1;
    // STATUS:
    // Upcoming: -3
    // Ready: -2
    // Pending: 0
    // Declined: -1
    // Approved: 1

    protected $table = 'overtime_requests';
    protected $fillable = ['employee_id', 'start_time', 'end_time', 'remarks', 'time_in_out', 'duration_in_minutes', 'dtr_date'];
    protected $casts = [
        'time_in_out' => 'object'
    ];
    protected $dates = ['created_at', 'updated_at'];
    protected $appends = ['requestor_name'];

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    public function approvers()
    {
        return $this->hasManyDeep(
            '\App\ApprovalItemEmployee',
            ['\App\ApprovalRequest', '\App\ApprovalLevel', '\App\ApprovalItem'],
            [
                'id',
                'approval_request_id',
                'approval_level_id',
                'approval_item_id',
            ],
            [
                'approval_request_id',
                'id',
                'id',
                'id',
            ]
        );
    }

    public function requestor()
    {
        return $this->belongsTo('App\Employee', 'employee_id');
    }

    public function getRequestorNameAttribute()
    {
        return $this->requestor->personal_information->last_name . ', ' .
            $this->requestor->personal_information->first_name . ' ' .
            $this->requestor->personal_information->middle_name;
    }

    public function authority_to_ot()
    {
        return $this->belongsTo('App\AuthorityToOt');
    }

    public function overtime_use()
    {
        return $this->hasMany('App\OvertimeUse');
    }
}
