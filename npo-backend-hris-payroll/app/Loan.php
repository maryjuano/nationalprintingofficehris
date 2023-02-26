<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    protected $table = 'loans';
    protected $fillable = ['loan_name', 'category'];
    protected $casts = [
        'status' => 'boolean'
    ];

    protected $dates = ['created_at', 'updated_at'];
}
