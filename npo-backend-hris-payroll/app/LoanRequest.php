<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoanRequest extends Model
{
  protected $table = 'loan_requests';
  protected $fillable = [];
  protected $casts = [
    'attachments' => 'array',
  ];
  protected $appends = [
    'requestor_name',
    'direct_report',
    'loan_name',
    'amount_paid',
    'remaining_balance',
    'department',
    'due_date'
  ];
  protected $dates = ['created_at', 'updated_at'];


  public function approval_request()
  {
    return $this->belongsTo('App\ApprovalRequest', 'approval_request_id');
  }

  use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

  public function approvers()
  {
    return $this->hasManyDeep(
      '\App\ApprovalItemEmployee',
      ['\App\ApprovalRequest', '\App\ApprovalLevel', '\App\ApprovalItem'],
      [
        'id',
        'approval_request_id',
        'approval_level_id',
        'approval_item_id',
      ],
      [
        'approval_request_id',
        'id',
        'id',
        'id',
      ]
    );
  }

  public function employment_details()
  {
    return $this->hasOneDeep(
      'App\EmploymentAndCompensation',
      ['App\Employee'],
      ['id', 'employee_id'],
      ['employee_id', 'id']
    );
  }

  public function loan_payments()
  {
    return $this->hasMany('App\LoanPayment', 'loan_request_id');
  }

  public function getDepartmentAttribute()
  {
    return $this->employment_details->department->department_name;
  }

  public function getDirectReportAttribute()
  {
    if (!$this->employment_details->direct_report) {
      return '---';
    }
    return $this->employment_details->direct_report->personal_information->last_name . ', ' .
      $this->employment_details->direct_report->personal_information->first_name . ' ' .
      $this->employment_details->direct_report->personal_information->middle_name;
  }

  public function department()
  {
    return $this->hasOneDeep(
      'App\Department',
      ['App\Employee', 'App\EmploymentAndCompensation'],
      ['id', 'employee_id', 'id'],
      ['employee_id', 'id', 'department_id']
    )->withIntermediate('App\Department', ['id', 'department_name', 'code']);;
  }

  public function loan_type()
  {
    return $this->belongsTo('\App\Loan', 'loan_type_id');
  }

  public function getLoanNameAttribute()
  {
    return $this->loan_type->loan_name;
  }

  public function requestor()
  {
    return $this->belongsTo('App\Employee', 'employee_id');
  }

  public function getRequestorNameAttribute()
  {
    return $this->requestor->personal_information->last_name . ', ' .
      $this->requestor->personal_information->first_name . ' ' .
      $this->requestor->personal_information->middle_name;
  }

  public function payments()
  {
    return $this->hasMany('App\LoanPayment');
  }

  public function payments_sum()
  {
    return $this->hasOne('App\LoanPayment')
      ->selectRaw('SUM(amount) as total_amount, loan_request_id')
      ->groupBy('loan_request_id');
  }

  public function getAmountPaidAttribute()
  {
    if ($this->status === 1) {
      return $this->payments_sum ? (float) $this->payments_sum->total_amount : 0.00;
    } else {
      return null;
    }
  }

  public function getRemainingBalanceAttribute()
  {
    if ($this->status === 1) {
      return $this->loan_amount - $this->amount_paid;
    } else {
      return 0;
    }
  }

  public function getDueDateAttribute()
  {
    if ($this->status === 1) {
      return Carbon::parse($this->updated_at)->addMonth($this->ammortization_number)->format('Y-m-d');
    } else {
      return null;
    }
  }
}
