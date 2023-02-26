<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoanDocuments extends Model
{
    protected $table = 'loan_document_approvers';
    protected $fillable = [];
    protected $casts = [];

    protected $dates = ['created_at', 'updated_at'];
}
