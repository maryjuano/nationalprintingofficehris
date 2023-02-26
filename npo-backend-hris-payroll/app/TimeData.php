<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeData extends Model
{
  protected $table = "time_data";
  protected $fillable = ['time_data_name', 'multiplier', 'id'];
  protected $casts = [
    'status' => 'boolean'
  ];
  protected $dates = ['created_at', 'updated_at'];
}
