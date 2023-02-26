<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DocumentRequestType extends Model
{
    protected $table = "document_request_type";
    protected $fillable = [];
    protected $casts = [
        'is_active' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];
}
