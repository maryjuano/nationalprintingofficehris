<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Biometrics extends Model
{
    protected $table = "biometrics";
    protected $fillable = ['id', 'employeeId', 'attendance', 'type',];
    public $timestamps = true;
}
