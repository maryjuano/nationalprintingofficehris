<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportsExtraData extends Model
{
    use HasFactory;
    protected $fillable = [ 'report', 'data' ];
    protected $casts = [
        'data' => 'object'
    ];
}
