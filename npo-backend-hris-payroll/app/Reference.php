<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    protected $table = "references";
    protected $fillable = [
        'id',
        'ref_tel_no',
        'ref_name',
        'ref_address',
    ];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];
}
