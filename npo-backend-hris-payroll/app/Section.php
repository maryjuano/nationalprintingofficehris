<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Section extends Model
{
    protected $table = 'sections';
    protected $fillable = ['department_id', 'section_name'];
    protected $dates = ['created_at', 'updated_at'];
}
