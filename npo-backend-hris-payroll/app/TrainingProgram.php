<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TrainingProgram extends Model
{
    protected $table = "training_program";
    protected $fillable = [
        'id',
        'title',
        'number_of_hours',
        'type',
        'sponsor',
        'created_at',
        'updated_at',
        'start_inclusive_date',
        'end_inclusive_date',
    ];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];
}
