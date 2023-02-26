<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EditRequest extends Model
{
    protected $table = 'edit_requests';
    protected $fillable = ['id', 'user_id', 'department_id', 'tab', 'purpose', 'status', 'remarks'];
    protected $casts = [
        'old' => 'array',
        'new' => 'array',
        'attachments' => 'array'
    ];
    protected $dates = ['created_at', 'updated_at'];


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
}
