<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Document extends Model
{
    protected $table = "documents";
    protected $fillable = [
        'id', 'employee_id', 'file_name', 'edit_req_id',
        'file_date', 'file_name', 'file_location', 'file_remarks',
        'employee_case_id', 'uid', 'file_type'
    ];
    protected $casts = [];

    protected $dates = ['created_at', 'updated_at', 'file_date'];
}
