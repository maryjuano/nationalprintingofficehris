<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeOffDetails extends Model
{

    protected $table = 'time_off_request_details';
    protected $fillable = [];

    protected $dates = ['created_at', 'updated_at'];
}
