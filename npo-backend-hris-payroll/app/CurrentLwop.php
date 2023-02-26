<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentLwop extends Model
{
    use HasFactory;

    protected $fillable = [
        'lwop',
        'employee_id'
    ];
}
