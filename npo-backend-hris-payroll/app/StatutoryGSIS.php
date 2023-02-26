<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StatutoryGSIS extends Model
{
    protected $table = 'gsis';
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = [
        'id', 'ecc', 'personal_share', 'government_share', 'status'
    ];
}
