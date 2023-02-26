<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class SalaryController extends Controller
{
  public function create_or_update(Request $request, \App\Salary $salary, $is_new = false)
  {
    $validator_arr = [
      'grade' => 'required|between:1,33',
      'step' => 'required',
      'effectivity_date' => 'required|date',
    ];

    $validator = Validator::make($request->all(), $validator_arr);

    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }

    $salary->grade = $request->input('grade');
    $salary->step = $request->input('step');
    $salary->effectivity_date = $request->input('effectivity_date');

    \DB::beginTransaction();
    try {
      if ($is_new) {
        $salary->created_by = $this->me->id;
        $salary->updated_by = $this->me->id;
        $salary->save();

        $this->log_user_action(
          Carbon::parse($salary->created_at)->toDateString(),
          Carbon::parse($salary->created_at)->toTimeString(),
          $this->me->id,
          $this->me->name,
          "Added Salary Grade " . $salary->grade,
          "HR & Payroll"
        );
      } else {
        $salary->updated_by = $this->me->id;
        $salary->save();

        $this->log_user_action(
          Carbon::parse($salary->created_at)->toDateString(),
          Carbon::parse($salary->updated_at)->toTimeString(),
          $this->me->id,
          $this->me->name,
          "Updated Salary Grade " . $salary->grade,
          "HR & Payroll"
        );
      }
      \DB::commit();
      return response()->json($salary);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }

  public function create(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['create_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, new \App\Salary(), true);
  }

  public function read(Request $request, \App\Salary $salary)
  {
    $unauthorized = $this->is_not_authorized(['view_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return response()->json($salary);
  }

  public function update(Request $request, \App\Salary $salary)
  {
    $unauthorized = $this->is_not_authorized(['edit_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, $salary);
  }

  public function get_active_salary_grade(Request $request, $salary_grade)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }

    $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
      ->orderBy('effectivity_date', 'desc')->first();
    if (!$active_salary_tranche) {
      return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
    }

    $salary_grade = \App\Salary::where([
      'salary_tranche_id' => $active_salary_tranche->id,
      'grade' => $salary_grade
    ])->first();

    return response()->json(['result' => 'success', 'data' => $salary_grade]);
  }

  public function create_salary_tranche(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['create_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $validator_arr = [
      'effectivity_date' => 'required|date|unique:App\Salary,effectivity_date',
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
        if ($step < $previousValuePrice) {
          return response()->json([
            'error' => 'Invalid request.',
            'message' => 'Somethings wrong with salary grade: ' . $salary_grade['grade'] . ' . Please make sure your salary grades and steps do not overlap.',
          ], 400);
        } else {
          $previousValuePrice = $step;
        }
      }
    }

    $salaries = [];
    \DB::beginTransaction();
    try {
      foreach ($salary_grades as $salary_grade) {
        $new_salary = new \App\Salary();
        $new_salary->effectivity_date = $effectivity_date;
        $new_salary->grade = $salary_grade['grade'];
        $new_salary->step = $salary_grade['step'];
        $new_salary->save();

        array_push($salaries, $new_salary);
      }

      \DB::commit();
      return response()->json(array('effectivity_date' => $effectivity_date, 'salary_grades' => $salaries));
    } catch (\Exception $exception) {
      \DB::rollback();
      throw $exception;
    }
  }

  public function update_salary_tranche(Request $request, \App\Salary $salary)
  {
    $unauthorized = $this->is_not_authorized(['edit_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $validator_arr = [
      'old_effectivity_date' => 'required|date|exists:App\Salary,effectivity_date',
      'effectivity_date' => 'required|date',
      'salary_grades' => 'required|array',
      'salary_grades.*.grade' => 'required|between:1,33|distinct',
      'salary_grades.*.step' => 'required|array',
    ];
    $validator = Validator::make($request->all(), $validator_arr);
    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }
    return $this->create_or_update($request, $salary);
  }

  public function list(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }

    $query = \App\Salary::select('*');

    $ALLOWED_FILTERS = [];
    $SEARCH_FIELDS = [];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

    return response()->json($response);
  }

  public function delete(Request $request, \App\_TModel $_tmodel)
  {
    $unauthorized = $this->is_not_authorized(['edit_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }
    $_tmodel->delete();
    return response()->json(array('_tmodel' => $_tmodel, 'result' => 'deleted'));
  }

  public function set_status(Request $request, $salary)
  {
    $unauthorized = $this->is_not_authorized(['edit_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $validator_arr = ['status' => 'required'];
    $validator = Validator::make($request->all(), $validator_arr);

    $query = \DB::table('salaries');
    $query->where('grade', '=',  $salary)
      ->update(array(
        "status" => request('status'),
        "updated_at" => Carbon::now()
      ));

    $salaries = \DB::table('salaries')->where('grade', $salary)->get();

    $query = \DB::table('salaries');
    $query->where('grade', '=',  $salary)
      ->update(array(
        "status" => request('status'),
        "updated_at" => Carbon::now()
      ));

    $salaries = \DB::table('salaries')->where('grade', $salary)->get();

    $this->log_user_action(
      Carbon::now(),
      Carbon::now(),
      $this->me->id,
      $this->me->name,
      json_encode($salaries[0]->status) == "1" ? "Activated Salary Grade" . $salaries[0]->grade : "Deactivated Salary Grade" . $salaries[0]->grade,
      "HR & Payroll"
    );

    return response()->json([
      "status" => json_encode($salaries[0]->status) == "1" ? true : false,
      "grade" => json_encode($salaries[0]->grade)
    ]);
  }

  // salary ranges
  public function read_salary_range(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['view_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $salary_ranges = \App\SalaryRanges::where('id', 1)->first();

    return response()->json($salary_ranges);
  }

  public function update_salary_range(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['edit_salary_tranche']);
    if ($unauthorized) {
      return $unauthorized;
    }

    \App\SalaryRanges::where('id', 1)
      ->update(array(
        'max_grades' => request('max_grades'), 'max_steps' => request('max_steps')
      ));

    $salary_ranges = \App\SalaryRanges::where('id', 1)->first();
    return response()->json($salary_ranges);
  }
}
