<?php

namespace App;

use App\Helpers\DTRStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dtr extends Model
{
    use HasFactory;
    protected $table = 'dtrs';
    protected $fillable = [
        'dtr_submit_id',
        'dtr_date',
        'employee_id',
        'in',
        'out',
        'break_start',
        'break_end',
        'holiday',
        'overtime_request',
        'time_off_request',
        'is_restday',
        'rendered_minutes',
        'overtime_minutes',
        'late_minutes',
        'undertime_minutes',
        'absence',
        'overtime',
        'late_for_payment_deduction',
        'late_for_vl_deduction',
        'absence_for_payment_deduction',
        'absence_for_vl_deduction',
        'absence_for_sl_deduction',
        'night_differential_minutes'
    ];
    protected $casts = [
        //'dtr_date' => 'date',
        'in' => 'object',
        'out' => 'object',
        'break_start' => 'object',
        'break_end' => 'object',
        'holiday' => 'object',
        'overtime_request' => 'object',
        'time_off_request' => 'object',
        'overtime' => 'array',
        'late_for_vl_deduction' => 'float',
        'late_for_payment_deduction' => 'float',
        'is_restday' => 'boolean'
    ];

    public function dtr_submit()
    {
        return $this->belongsTo('\App\DtrSubmit', 'dtr_submit_id');
    }

    public function scopeApproved($query)
    {
        return $query->whereHas('dtr_submit', function ($q) {
            return $q->whereStatus(DTRStatus::APPROVED);
        });
    }
}
