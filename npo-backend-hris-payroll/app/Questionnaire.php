<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Questionnaire extends Model
{
    protected $table = "questionnaires";
    protected $fillable = [
        'id', 'employee_id', 'third_degree_relative', 'third_degree_relative_details', 'fourth_degree_relative',
        'fourth_degree_relative_details', 'administrative_offender', 'administrative_offender_details', 'criminally_charged',
        'criminally_charged_data', 'convicted_of_crime', 'convicted_of_crime_details', 'separated_from_service',
        'separated_from_service_details', 'election_candidate', 'election_candidate_details', 'resigned_from_gov', 'resigned_from_gov_details',
        'multiple_residency', 'multiple_residency_country', 'indigenous', 'indigenous_group', 'pwd', 'pwd_id', 'solo_parent',
        'solo_parent_id'
    ];

    protected $casts = [
        'criminally_charged_data' => 'array'
    ];
    protected $dates = ['created_at', 'updated_at'];
}
