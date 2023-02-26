<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrSubmit extends Model
{
    use HasFactory;
    /*
        STATUS
        ------------------------
        PENDING = 0,
        PENDING_TRANSACTION = 1,
        SUBMITTED = 2,
        APPROVED = 3,
        REJECTED = 4,
    */
    protected $table = 'dtr_submits';
    protected $fillable = ['employee_id', 'approval_request_id'];
    protected $casts = [];

    public function dtrs()
    {
        return $this->hasMany('\App\Dtr', 'dtr_submit_id');
    }
}
