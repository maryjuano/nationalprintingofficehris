<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PersonalInformation extends Model
{
    protected $table = "personal_information";
    protected $fillable = [
        'id',
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'place_of_birth',
        'date_of_birth',
        'civil_status',
        'height',
        'weight',
        'blood_type',
        'gender',

        'citizenship',
        'dual_citizenship',
        'country',
        'by',

        'house_number',
        'street',
        'subdivision',
        'barangay',
        'city',
        'province',
        'zip_code',

        'same_addresses',

        'p_house_number',
        'p_street',
        'p_subdivision',
        'p_barangay',
        'p_city',
        'p_province',
        'p_zip_code',

        'area_code',
        'telephone_number',
        'mobile_number',
        'email_address'
    ];
    protected $dates = [
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'dual_citizenship' => 'boolean',
        'same_addresses' => 'boolean',
        'mobile_number' => 'array',
        'height' => 'float',
        'weight' => 'float',
    ];

    protected $appends = [
        'civil_status_str',
        'gender_str',
        'blood_type_str'
    ];


    public function getCivilStatusStrAttribute()
    {
        switch ($this->civil_status) {
            case 1:
                return 'Single';
            case 2:
                return 'Married';
            case 3:
                return 'Divorced';
            case 4:
                return 'Separated';
            case 5:
                return 'Widowed';
            default:
                return '';
        }
    }

    public function getGenderStrAttribute()
    {
        switch ($this->gender) {
            case 1:
                return 'Male';
            default:
                return 'Female';
        }
    }

    public function getBloodTypeStrAttribute()
    {
        switch ($this->blood_type) {
            case 1:
                return 'A+';
            case 2:
                return 'A-';
            case 3:
                return 'O+';
            case 4:
                return 'O-';
            case 5:
                return 'B+';
            case 6:
                return 'B-';
            case 7:
                return 'AB+';
            case 7:
                return 'AB-';
            default:
                return '-';
        }
    }
}
