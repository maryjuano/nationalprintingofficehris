<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SalaryRanges extends Model
{
    protected $table = 'salary_ranges';
    protected $dates = ['created_at', 'updated_at'];
}
