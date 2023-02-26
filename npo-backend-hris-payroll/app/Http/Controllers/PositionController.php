<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class PositionController extends Controller
{
  public function create_or_update(Request $request, \App\Position $position, $is_new = false)
  {
    \DB::beginTransaction();
    try {
      if (request('item_number') == $position->item_number && !$is_new) {
        $position->item_number = "";
        $position->save();
      }

      if ($is_new) {
        $validator_arr = [
          'position_name' => 'required',
          'department_id' => 'required|exists:departments,id',
          'salary_grade' => 'required',
          'item_number' => 'required|unique:positions',
        ];
        $error_message = [
          'item_number.unique' => 'Oops! Item Number already exists.',
        ];
      } else {
        $validator_arr = [
          'item_number' => 'unique:positions',
        ];
        $error_message = [
          'item_number.unique' => 'Oops! Item Number already exists.',
        ];
      }

      $validator = Validator::make($request->all(), $validator_arr, $error_message);
      if ($validator->fails()) {
        return response()->json(['error' => 'validation_failed', 'message' => $validator->errors()->first()], 400);
      }

      $position->position_name = request('position_name');
      $position->department_id = request('department_id');
      $position->salary_grade = request('salary_grade');
      $position->item_number = request('item_number');

      //not required fields
      $position->is_active = request('status', 1);

      if ($is_new) {
        $position->created_by = $this->me->id;
        $position->updated_by = $this->me->id;
        $position->vacancy = 1;
        $position->save();
      } else {
        $position->updated_by = $this->me->id;
        $position->save();
      }

      \DB::commit();

      $this->log_user_action(
        Carbon::parse($position->created_at)->toDateString(),
        Carbon::parse($position->created_at)->toTimeString(),
        $this->me->id,
        $this->me->name,
        "Created " . $position->position_name . " as Position",
        "HR & Payroll"
      );
      return $this->read($request, $position);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }

  public function create(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['create_position']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, new \App\Position(), true);
  }

  public function update(Request $request, \App\Position $position)
  {
    $unauthorized = $this->is_not_authorized(['edit_position']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, $position);
  }

  public function read(Request $request, \App\Position $position)
  {
    $unauthorized = $this->is_not_authorized(['view_positions']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $position->loadMissing('department');
    return response()->json($position);
  }

  public function list(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }

    $query = \App\Position::with('department');

    if ($request->filled('whitelist')) {
      $query->whereIn('id', $request->input('whitelist'));
    }

    $ALLOWED_FILTERS = ['vacancy', 'salary_grade', 'is_active'];
    $SEARCH_FIELDS = ['position_name'];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS, "", true);

    return response()->json($response);
  }

  public function get_unique_positions(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }

    $query = \App\Position::groupBy('position_name');

    $ALLOWED_FILTERS = ['vacancy', 'is_active'];
    $SEARCH_FIELDS = ['position_name'];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

    return response()->json($response);
  }

  public function set_status(Request $request, \App\Position $position)
  {
    $unauthorized = $this->is_not_authorized(['toggle_position']);
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

    $employeesExist = \App\EmploymentAndCompensation::where('position_id', $position->id)->exists();
    if ($employeesExist) {
      return response()->json(array("error" => "Update Failed!", "message" => "Sorry! This position is filled."));
    }

    \DB::beginTransaction();
    try {
      $position->is_active = request('status');
      $position->updated_by = $this->me->id;
      $position->save();
      \DB::commit();
      $this->log_user_action(
        Carbon::now(),
        Carbon::now(),
        $this->me->id,
        $this->me->name,
        $position->is_active == true ? "Activated Position " . $position->position_name : "Deactivated Position " . $position->position_name,
        "HR & Payroll"
      );
      $position->loadMissing('department');
      return response()->json($position);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }
}
