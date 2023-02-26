<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Signatories extends Model
{
    use HasFactory;

    protected $table = 'signatories';
    protected $fillable = [
        'signatories',
        'report_name',
        'signatories_count'
    ];
    protected $casts = [
        'signatories' => 'array'
    ];
    protected $dates = ['created_at', 'updated_at'];
}
