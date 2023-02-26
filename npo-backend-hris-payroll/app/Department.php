<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Department extends Model
{
    protected $table = "departments";
    protected $fillable = ['department_name', 'code', 'pap_code'];
    protected $casts = [
        'sections' => 'array',
        'is_active' => 'boolean'
    ];
    protected $appends = [];
    protected $dates = ['created_at', 'updated_at'];

    public function sections()
    {
        return $this->hasMany('App\Section');
    }

    public function employees()
    {
        return $this->hasMany('App\EmploymentAndCompensation', 'department_id');
    }

    public function employment_and_compensation()
    {
        return $this->hasMany('App\EmploymentAndCompensation', 'department_id')
            ->leftJoin('positions', 'positions.id', '=', 'employment_and_compensation.position_id')
            ->leftJoin('salaries', 'salaries.id', '=', 'employment_and_compensation.salary_grade_id')
            ->select(
                'employment_and_compensation.department_id',
                'employment_and_compensation.employee_id',
                'employment_and_compensation.position_id',
                'employment_and_compensation.account_name',
                'employment_and_compensation.salary_grade_id',
                'employment_and_compensation.step_increment',
                'salaries.step',
                'positions.id',
                'position_name',
                'positions.item_number'
            );
    }
    public function positions()
    {
        return $this->hasMany('App\Position', 'department_id')
            ->leftJoin('salaries', 'salaries.id', '=', 'positions.salary_grade')
            ->leftJoin('employment_and_compensation', 'employment_and_compensation.position_id', '=', 'positions.id')
            ->leftJoin('personal_information', 'employment_and_compensation.employee_id', '=', 'personal_information.employee_id')
            // ->where('vacancy', 0) //0 is filled
            ->where('is_active', 1)
            ->select(
                'positions.department_id',
                'item_number',
                'positions.position_name',
                'positions.salary_grade as salary_grade_id',
                'employment_and_compensation.step_increment',
                'employment_and_compensation.account_name',
                'grade',
                'step',
                \DB::raw("CONCAT(personal_information.last_name,', ',personal_information.first_name, ' ', personal_information.middle_name) as full_name")
                // 'vacancy'
            )
            ->orderBy('positions.salary_grade', 'DESC');
    }
    public function unfilled_positions()
    {
        return $this->hasMany('App\Position', 'department_id')
            ->leftJoin('salaries', 'salaries.id', '=', 'positions.salary_grade')
            ->where('vacancy', \App\Position::VACANCY_UNFILLED) // 1 is unfilled
            ->where('is_active', 1)
            ->select(
                'positions.department_id',
                'positions.salary_grade as salary_grade_id',
                'item_number',
                'positions.position_name',
                'grade',
                'step',
                // 'vacancy',
            )
            ->orderBy('grade');
    }
}
