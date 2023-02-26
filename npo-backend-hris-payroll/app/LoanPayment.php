<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoanPayment extends Model
{
    protected $table = 'loan_payments';
    protected $fillable = [];
    protected $casts = [];
    protected $appends = [];
    protected $dates = ['created_at', 'updated_at'];
}
