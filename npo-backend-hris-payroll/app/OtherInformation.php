<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtherInformation extends Model
{
    protected $table = "other_information";
    protected $fillable = [
        'id',
        'employee_id',
        'special_skills',
        'recognition',
        'organization',
        'created_at',
        'updated_at',
    ];
    protected $dates = ['created_at', 'updated_at'];
}
