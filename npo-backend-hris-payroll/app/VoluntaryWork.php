<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VoluntaryWork extends Model
{
    protected $table = "voluntary_work";
    protected $fillable = [
        'id',
        'name_of_organization',
        'address',
        'number_of_hours',
        'position',
        'created_at',
        'updated_at',
        'start_inclusive_date',
        'end_inclusive_date',
    ];
    protected $dates = ['created_at', 'updated_at'];
}
