<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class Offboard extends Model
{
    use HasFactory;

    protected $table = 'offboard';
    protected $fillable = [
        'reason',
        'effectivity',
        'employee_id'
    ];
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [
        'attachments' => 'array'
    ];
}
