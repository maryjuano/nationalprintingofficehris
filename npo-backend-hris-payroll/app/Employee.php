<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Employee extends Model
{
    protected $table = 'employees';
    protected $fillable = ['id', 'users_id', 'employee_id', 'status'];
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [];
    protected $appends = ['name', 'department'];


    public function personal_information()
    {
        return $this->hasOne('App\PersonalInformation', 'employee_id');
    }

    public function getNameAttribute()
    {
        return $this->personal_information->last_name . ', ' .
            $this->personal_information->first_name . ' ' .
            $this->personal_information->middle_name . ' ' .
            ($this->personal_information->name_extension == 'NA' ? '' : ($this->personal_information->name_extension ?? ''));
    }

    public function employee_number()
    {
        return $this->hasOne('App\EmployeeIdNumber', 'employee_id');
    }

    public function getIdNumberAttribute()
    {
        return !$this->employee_number ? '' : $this->employee_number->id_number;
    }

    public function family_background()
    {
        return $this->hasOne('App\FamilyBackground', 'employee_id');
    }

    public function employment_and_compensation()
    {
        return $this->hasOne('App\EmploymentAndCompensation');
    }

    public function employee_stub()
    {
        return $this->hasOne('App\EmployeeStub');
    }

    public function educational_background()
    {
        return $this->hasMany('App\EducationalBackground', 'employee_id');
    }

    public function civilservice_eligibility()
    {
        return $this->hasMany('App\CivilService', 'employee_id');
    }

    public function work_experience()
    {
        return $this->hasMany('App\WorkExperience', 'employee_id');
    }

    public function voluntary_work()
    {
        return $this->hasMany('App\VoluntaryWork', 'employee_id');
    }

    public function training_program()
    {
        return $this->hasMany('App\TrainingProgram', 'employee_id');
    }

    public function other_information()
    {
        return $this->hasMany('App\OtherInformation', 'employee_id');
    }

    public function questionnaire()
    {
        return $this->hasOne('App\Questionnaire', 'employee_id');
    }

    public function profile_picture()
    {
        return $this->hasOne('App\ProfilePicture', 'employee_id');
    }

    public function govt_id()
    {
        return $this->hasOne('App\GovernmentId', 'employee_id');
    }

    public function references()
    {
        return $this->hasMany('App\Reference', 'employee_id');
    }

    public function getDepartmentAttribute()
    {
        return !$this->employment_and_compensation ? null
            : (!$this->employment_and_compensation->department ? null
                : $this->employment_and_compensation->department->department_name);
    }

    public function getSectionAttribute()
    {
        return !$this->employment_and_compensation ? null
            : (!$this->employment_and_compensation->section ? null
                : $this->employment_and_compensation->section->section_name);
    }

    public function getEmployeeTypeAttribute()
    {
        return !$this->employment_and_compensation ? null
            : (!$this->employment_and_compensation->employee_type ? null
                : $this->employment_and_compensation->employee_type->employee_type_name);
    }

    public function getSalaryAttribute()
    {
        $employee_is_temporary = $this->employment_and_compensation->employee_type->id === \App\EmployeeType::COS ||
            $this->employment_and_compensation->employee_type->id === \App\EmployeeType::JOB_ORDER;
        if ($employee_is_temporary) {
            return $this->employment_and_compensation->salary_rate ?? 0;
        } else {
            return $this->employment_and_compensation->salary->step[$this->employment_and_compensation->step_increment ?? 0] ?? 0;
        }
    }

    public function getPositionAttribute()
    {
        $employee_is_temporary = $this->employment_and_compensation->employee_type->id === \App\EmployeeType::COS ||
            $this->employment_and_compensation->employee_type->id === \App\EmployeeType::JOB_ORDER;
        if ($employee_is_temporary) {
            return $this->employment_and_compensation->position_name;
        } else {
            return $this->employment_and_compensation->position->position_name;
        }
    }

    public function time_off_balances()
    {
        return $this->hasMany('App\TimeOffBalance', 'employee_id');
    }

    public function offboard()
    {
        return $this->hasOne('App\Offboard', 'employee_id');
    }

    public function system_information()
    {
        return $this->hasOne('App\SystemInformation', 'employee_id');
    }

    public function payroll_employee_logs()
    {
        return $this->hasMany('App\PayrollEmployeeLog', 'employee_id');
    }

    public function biometrics()
    {
        return $this->hasManyThrough(
            'App\Biometrics',
            'App\EmployeeIdNumber',
            'employee_id',
            'employeeId',
            'id',
            'id_number'
        );
    }

    public function time_off_requests()
    {
        return $this->hasMany('\App\TimeOffRequest', 'employee_id');
    }

    public function overtime_requests()
    {
        return $this->hasMany('\App\OvertimeRequest', 'employee_id');
    }

    public function dtr_submits()
    {
        return $this->hasMany('\App\DtrSubmit', 'employee_id');
    }

    public function dtrs()
    {
        return $this->hasMany('\App\Dtr', 'employee_id');
    }

    public function edit_history_first_name()
    {
        return $this->returnInfoType('first_name');
    }
    public function edit_history_last_name()
    {
        return $this->returnInfoType('last_name');
    }
    public function edit_history_middle_name()
    {
        return $this->returnInfoType('middle_name');
    }
    public function edit_history_name_extension()
    {
        return $this->returnInfoType('name_extension');
    }
    public function edit_history_zip_code()
    {
        return $this->returnInfoType('zip_code');
    }
    public function edit_history_mobile_number()
    {
        return $this->returnInfoType('mobile_number');
    }
    public function edit_history_email_address()
    {
        return $this->returnInfoType('email_address');
    }
    public function edit_history_civil_status()
    {
        return $this->returnInfoType('civil_status');
    }
    public function edit_history_date_of_birth()
    {
        return $this->returnInfoType('date_of_birth');
    }
    public function edit_history_place_of_birth()
    {
        return $this->returnInfoType('place_of_birth');
    }
    public function edit_history_position_old()
    {
        return $this->hasOne('App\EditHistory', 'employee_id')
            ->join('positions', 'positions.id', '=', 'edit_histories.old')
            ->select('employee_id', 'old', 'positions.position_name')
            ->whereBetween(\DB::raw('date(edit_histories.created_at)'), [request('start'), request('end')])
            ->where('information_type', 'position_id')
            ->orderBy('edit_histories.created_at', 'DESC');
    }
    public function edit_history_position_new()
    {
        return $this->hasOne('App\EditHistory', 'employee_id')
            ->join('positions', 'positions.id', '=', 'edit_histories.new')
            ->select('employee_id', 'old', 'positions.position_name')
            ->whereBetween(\DB::raw('date(edit_histories.created_at)'), [request('start'), request('end')])
            ->where('information_type', 'position_id')
            ->orderBy('edit_histories.created_at', 'DESC');
    }
    public function edit_history_employee_type_old()
    {
        return $this->hasOne('App\EditHistory', 'employee_id')
            ->join('employee_types', 'employee_types.id', '=', 'edit_histories.old')
            ->select('employee_id', 'old', 'employee_types.employee_type_name')
            ->whereBetween(\DB::raw('date(edit_histories.created_at)'), [request('start'), request('end')])
            ->where('information_type', 'employee_type_id')
            ->orderBy('edit_histories.created_at', 'DESC');
    }
    public function edit_history_employee_type_new()
    {
        return $this->hasOne('App\EditHistory', 'employee_id')
            ->join('employee_types', 'employee_types.id', '=', 'edit_histories.new')
            ->select('employee_id', 'old', 'employee_types.employee_type_name')
            ->whereBetween(\DB::raw('date(edit_histories.created_at)'), [request('start'), request('end')])
            ->where('information_type', 'employee_type_id')
            ->orderBy('edit_histories.created_at', 'DESC');
    }
    private function returnInfoType($type)
    {
        return $this->hasOne('App\EditHistory', 'employee_id')
            ->select('employee_id', 'old', 'new')
            ->whereBetween(\DB::raw('date(created_at)'), [request('start'), request('end')])
            ->where('information_type', $type)
            ->orderBy('created_at', 'DESC');
    }
}
