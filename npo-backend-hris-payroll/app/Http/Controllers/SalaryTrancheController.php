<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class SalaryTrancheController extends Controller
{
    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['create_salary_tranche']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'effectivity_date' => 'required|date|unique:App\SalaryTranche,effectivity_date',
            'salary_grades' => 'required|array',
            'salary_grades.*.grade' => 'required|between:1,33|distinct',
            'salary_grades.*.step' => 'required|array',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $effectivity_date = $request->input('effectivity_date');
        $salary_grades  = $request->input('salary_grades');

        usort($salary_grades, function ($a, $b) {
            return $a['grade'] - $b['grade'];
        });

        $previousValuePrice = 0;
        foreach ($salary_grades as $salary_grade) {
            $steps = $salary_grade['step'];
            foreach ($steps as $step) {
                if ($step == null || $step < $previousValuePrice) {
                    return response()->json([
                        'error' => 'Invalid request.',
                        'message' => 'Somethings wrong with salary grade: ' . $salary_grade['grade'] . ' . Please make sure your salary grades and steps do not overlap.',
                    ], 400);
                } else {
                    $previousValuePrice = $step;
                }
            }
        }

        \DB::beginTransaction();
        try {
            $old_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
            $old_salary_tranche->loadMissing('salaries');

            $new_salary_tranche = new \App\SalaryTranche();
            $new_salary_tranche->is_active = true;
            $new_salary_tranche->effectivity_date = $effectivity_date;
            $new_salary_tranche->created_by = $this->me->id;
            $new_salary_tranche->save();

            foreach ($salary_grades as $salary_grade) {
                $new_salary = new \App\Salary();
                $new_salary->salary_tranche_id = $new_salary_tranche->id;
                $new_salary->grade = $salary_grade['grade'];
                $new_salary->step = $salary_grade['step'];
                $new_salary->save();
            }
            $new_salary_tranche->loadMissing('salaries');

            if ($old_salary_tranche) {
                \App\Notification::create_hr_notification(
                    ['nosa'],
                    'Salary Tranche has been updated with effectivity date ' . Carbon::parse($new_salary_tranche->effectivity_date)->format('m/d/Y'),
                    \App\Notification::NOTIFICATION_SOURCE_TRANCHE,
                    $new_salary_tranche->id,
                    $new_salary_tranche
                );


                $old_salary_grades = $old_salary_tranche->salaries->keyBy('grade');
                $new_salary_grades = $new_salary_tranche->salaries->keyBy('grade');
                // Process each employee and create notifications if needed
                $employees = \App\Employee::query()
                ->select('employees.*')
                ->leftJoin('employment_and_compensation', 'employees.id', 'employment_and_compensation.employee_id')
                ->where('employees.status', 1)
                ->whereNotIn('employment_and_compensation.employee_type_id', [\App\EmployeeType::COS, \App\EmployeeType::JOB_ORDER])
                ->get();
                foreach ($employees as $employee) {
                    $old = $new = null;
                    if (!isset($old_salary_grades[$employee->employment_and_compensation->salary_grade_id])) {
                        continue;
                    }
                    if (!isset($new_salary_grades[$employee->employment_and_compensation->salary_grade_id])) {
                        continue;
                    }
                    $old_grade = $old_salary_grades[$employee->employment_and_compensation->salary_grade_id];
                    $new_grade = $new_salary_grades[$employee->employment_and_compensation->salary_grade_id];
                    if ($old_grade == null || $new_grade == null) {
                        continue;
                    }
                    else {
                        $old = $old_grade->step[$employee->employment_and_compensation->step_increment];
                        $new = $new_grade->step[$employee->employment_and_compensation->step_increment];
                    }

                    if ($old == null || $new == null || $new == $old ) {
                        // same salary
                        continue;
                    }
                    else {
                        // create Nosa
                        $nosa = new \App\NoticeOfSalaryAdjustment();
                        $nosa->employee_id = $employee->id;
                        $nosa->generated_date = Carbon::now();
                        $nosa->effectivity_date = $new_salary_tranche->effectivity_date;
                        $nosa->old_rate = $old;
                        $nosa->new_rate = $new;
                        $nosa->old_step = $employee->employment_and_compensation->step_increment;
                        $nosa->new_step = $employee->employment_and_compensation->step_increment;
                        $nosa->old_grade = $employee->employment_and_compensation->salary_grade_id;
                        $nosa->new_grade = $employee->employment_and_compensation->salary_grade_id;
                        $nosa->old_position_id = $employee->employment_and_compensation->position_id;
                        $nosa->new_position_id = $employee->employment_and_compensation->position_id;
                        $nosa->remarks = 'From Salary Tranche adjustment';
                        $nosa->save();

                        $employee->employee_id = $employee->id;
                        \App\Notification::create_user_notification(
                            $employee->users_id,
                            'Your Salary has been adjusted from ' .
                            number_format($old,2) . ' to ' . number_format($new,2) .
                            ' (SG: ' . $nosa->new_step .
                            ' Step: ' . $nosa->new_step . ') ' .
                            ' effective ' . Carbon::parse($nosa->effectivity_date)->format('m/d/Y'),
                            \App\Notification::NOTIFICATION_SOURCE_NOSA,
                            $employee->id,
                            $employee
                        );
                    }
                }

            }

            \DB::commit();
            return response()->json(array('result' => 'success', 'data' => $new_salary_tranche));
        } catch (\Exception $exception) {
            \DB::rollback();
            throw $exception;
        }
    }

    public function update(Request $request, \App\SalaryTranche $salary_tranche)
    {
        $unauthorized = $this->is_not_authorized(['edit_salary_tranche']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'effectivity_date' => 'required|date|unique:App\SalaryTranche,effectivity_date,' . $salary_tranche->id . ',id',
            'salary_grades' => 'required|array',
            'salary_grades.*.grade' => 'required|between:1,33|distinct',
            'salary_grades.*.step' => 'required|array',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $effectivity_date = $request->input('effectivity_date');
        $salary_grades  = $request->input('salary_grades');

        usort($salary_grades, function ($a, $b) {
            return $a['grade'] - $b['grade'];
        });

        $previousValuePrice = 0;
        foreach ($salary_grades as $salary_grade) {
            $steps = $salary_grade['step'];
            foreach ($steps as $step) {
                if ($step == null || $step < $previousValuePrice) {
                    return response()->json([
                        'error' => 'Invalid request.',
                        'message' => 'Somethings wrong with salary grade: ' . $salary_grade['grade'] . ' . Please make sure your salary grades and steps do not overlap.',
                    ], 400);
                } else {
                    $previousValuePrice = $step;
                }
            }
        }

        \DB::beginTransaction();
        try {
            $salary_tranche->effectivity_date = $effectivity_date;
            $salary_tranche->updated_by = $this->me->id;
            $salary_tranche->save();

            foreach ($salary_grades as $salary_grade) {
                \App\Salary::updateOrCreate(
                    ['salary_tranche_id' => $salary_tranche->id, 'grade' => $salary_grade['grade']],
                    ['step' => $salary_grade['step'], 'updated_by' => $this->me->id]
                );
            }

            $salary_tranche->loadMissing('salaries');

            \DB::commit();
            return response()->json(array('result' => 'success', 'data' => $salary_tranche));
        } catch (\Exception $exception) {
            \DB::rollback();
            throw $exception;
        }
    }

    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\SalaryTranche::with('salaries');

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_active_salary_grades(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->where('is_active', true)
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        return response()->json(array('result' => 'success', 'data' => $active_salary_tranche->salaries));
    }
}
