<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Courses extends Model
{
    use HasFactory;

    protected $table = 'courses';
    protected $fillable = ['course_name', 'course_type'];
    protected $casts = [
        'status' => 'boolean'
    ];
    protected $dates = ['created_at', 'updated_at'];
}
