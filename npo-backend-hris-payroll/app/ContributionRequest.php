<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class ContributionRequest extends Model
{
    protected $table = "contribution_request";
    protected $fillable = ['contribution_type', 'amount', 'employee_id', 'remarks'];
    protected $casts = [
        'amount' => 'float'
    ];
    protected $dates = ['created_at', 'updated_at'];
    protected $appends = ['approved_at'];


    public function getApprovedAtAttribute()
    {
        return Carbon::parse($this->updated_at);
    }

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
