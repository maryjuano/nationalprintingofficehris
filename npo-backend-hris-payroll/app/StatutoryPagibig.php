<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StatutoryPagibig extends Model
{
    protected $table = 'pagibig';
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = [
        'id', 'minimum_range', 'maximum_range', 'personal_share', 'government_share',
    ];
}
