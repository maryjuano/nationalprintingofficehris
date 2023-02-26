<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovernmentId extends Model
{
    protected $fillable = [
        'id',
        'place_of_issue',
        'date_of_issue',
        'id_no',
        'id_type',
    ];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];
}
