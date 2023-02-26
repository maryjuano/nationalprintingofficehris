<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DocumentRequest extends Model
{
    protected $table = "document_request";
    protected $fillable = [];
    protected $casts = [];
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

    public function attachment()
    {
        return $this->belongsTo('\App\Document', 'extra_id');
    }
}
