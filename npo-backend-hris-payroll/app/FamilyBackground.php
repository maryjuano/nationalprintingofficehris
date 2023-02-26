<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FamilyBackground extends Model
{
    protected $table = 'family_background';
    protected $fillable = [
        'id',
        'employee_id',
        'spouse_last_name',
        'spouse_first_name',
        'spouse_middle_name',
        'name_extension',
        'occupation',
        'employer_name',
        'business_address',
        'telephone_number',
        'father_last_name',
        'father_first_name',
        'father_middle_name',
        'father_extension',
        'mother_last_name',
        'mother_first_name',
        'mother_middle_name',
        'children',
    ];
    protected $casts = [
        'children' => 'array'
    ];
    protected $dates = ['created_at', 'updated_at'];
}
