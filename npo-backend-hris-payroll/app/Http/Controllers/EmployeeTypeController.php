<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class EmployeeTypeController extends Controller
{
  public function create_or_update(Request $request, \App\EmployeeType $employee_type, $is_new = false)
  {
    if ($is_new) {
      $validator_arr = [
        'employee_type_name' => 'required|unique:employee_types',
        'time_offs_ids' => 'required|array',
        'time_offs_ids.*' => 'exists:time_offs,id'
      ];
    } else {
      $validator_arr = [
        'employee_type_name' => 'unique:employee_types,employee_type_name,' . $employee_type->id,
        'time_offs_ids' => 'required|array',
        'time_offs_ids.*' => 'exists:time_offs,id'
      ];
    }

    $validator = Validator::make($request->all(), $validator_arr);
    if ($validator->fails() and $is_new) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }

    $employee_type->fill(
      $request->only(['employee_type_name'])
    );

    \DB::beginTransaction();
    try {
      if ($is_new) {
        $employee_type->is_active = true;
        $employee_type->created_by = $this->me->id;
        $employee_type->updated_by = $this->me->id;
        $employee_type->save();
      } else {
        $employee_type->updated_by = $this->me->id;
        $employee_type->save();
      }

      $employee_type->time_offs()->sync($request->input('time_offs_ids'));

      $this->log_user_action(
        Carbon::parse($employee_type->created_at)->toDateString(),
        Carbon::parse($employee_type->created_at)->toTimeString(),
        $this->me->id,
        $this->me->name,
        "Created " . $employee_type->employee_type_name . " as Employee Type",
        "HR & Payroll"
      );

      \DB::commit();
      $employee_type->loadMissing('time_offs');
      $employee_type->loadCount('members');
      return response()->json($employee_type);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }

  public function create(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['create_employee_type']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, new \App\EmployeeType(), true);
  }

  public function read(Request $request, \App\EmployeeType $employee_type)
  {
    $unauthorized = $this->is_not_authorized(['view_employee_types']);
    if ($unauthorized) {
      return $unauthorized;
    }
    $employee_type->loadMissing('time_offs');
    $employee_type->loadCount('members');
    return response()->json($employee_type);
  }

  public function update(Request $request, \App\EmployeeType $employee_type)
  {
    $unauthorized = $this->is_not_authorized(['edit_employee_type']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, $employee_type);
  }

  public function list(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }

    $query = \App\EmployeeType::query()
    ->with('time_offs')
    ->withCount('members');

    if ($request->filled('sort_key') && $request->input('sort_key') === 'members_count') {
      $query->orderBy('members_count', 'desc');
    }

    // filtering
    $ALLOWED_FILTERS = ['employee_type_name', 'is_active'];
    $SEARCH_FIELDS = ['employee_type_name'];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

    return response()->json($response);
  }

  public function delete(Request $request, \App\_TModel $_tmodel)
  {
    $unauthorized = $this->is_not_authorized(['edit_employee_type']);
    if ($unauthorized) {
      return $unauthorized;
    }
    $_tmodel->delete();
    return response()->json(array('_tmodel' => $_tmodel, 'result' => 'deleted'));
  }

  public function set_status(Request $request, $employee_type_id)
  {
    $unauthorized = $this->is_not_authorized(['toggle_employee_type']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $validator_arr = [
      'status' => 'required'
    ];
    $validator = Validator::make($request->all(), $validator_arr);
    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'message' => $validator->errors()->first()], 400);
    }

    $employee_type = \App\EmployeeType::withCount('members')->find($employee_type_id);
    if (!$request->input('status') && $employee_type->members_count > 0) {
      return response()->json(array("error" => "Update Failed!", "message" => "Sorry! There are employees with this employee type."), 400);
    }

    \DB::beginTransaction();
    try {
      $employee_type->is_active = request('status');
      $employee_type->save();
      $this->log_user_action(
        Carbon::now(),
        Carbon::now(),
        $this->me->id,
        $this->me->name,
        $employee_type->is_active == true ? "Activated employee type " . $employee_type->employee_type_name : "Deactivated employee type " . $employee_type->employee_type_name,
        "HR & Payroll"
      );
      \DB::commit();
      return response()->json($employee_type);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }
}
