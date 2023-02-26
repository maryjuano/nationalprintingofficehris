<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CivilService extends Model
{
    protected $table = 'civil_service';
    protected $fillable = [
        'id',
        'employee_id',
        'government_id',
        'place',
        'license_no',
        'place_rating',
        'date',
        'validity_date',
        'created_at',
        'updated_at',
    ];
    protected $dates = ['created_at', 'updated_at'];
}
