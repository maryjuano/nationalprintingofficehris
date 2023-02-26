<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'user_logs';
    protected $fillable = ['id', 'date', 'time', 'name', 'activity', 'module'];
}
