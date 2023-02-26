<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class TimeOffController extends Controller
{
  public function create(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['create_time_off']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $validator_arr = [
      'time_off_type' => 'required|unique:time_offs',
      'time_off_code' => 'required|unique:time_offs',
      'unit' => 'required',
      'time_off_color_id' => 'required',
      'use_csl_matrix' => 'required',
      'default_balance' => 'required_if:use_csl_matrix,false',
      'cash_convertible' => 'required',
      'monthly_credit_balance' => 'required|numeric',
      'monthly_credit_date' => 'sometimes|date_format:Y-m-d',
      'annual_credit_reset_month' => 'required|numeric',
      'annual_credit_reset_day' => 'required|numeric',
      'minimum_used_credits' => 'required|numeric'
    ];

    $validator = Validator::make($request->all(), $validator_arr);

    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }

    \DB::beginTransaction();
    try {
      $time_off = \App\TimeOff::create($request->all());
      $time_off->created_by = $this->me->id;
      $time_off->updated_by = $this->me->id;
      $time_off->save();

      $this->log_user_action(
        Carbon::parse($time_off->created_at)->toDateString(),
        Carbon::parse($time_off->created_at)->toTimeString(),
        $this->me->id,
        $this->me->name,
        "Created " . $time_off->time_off_type . " as Time Off Type",
        "HR & Payroll"
      );

      \DB::commit();
      $time_off->loadMissing('color');
      return response()->json($time_off);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }

  public function update(Request $request, \App\TimeOff $time_off)
  {
    $unauthorized = $this->is_not_authorized(['edit_time_off']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $pending_time_offs = \App\TimeOffRequest::where('time_off_requests.status', 1)
      ->join('time_off_balance', 'time_off_balance.id', 'time_off_requests.time_off_balance_id')
      ->where('time_off_balance.time_off_id', $time_off->id)
      ->count();

    if ($pending_time_offs > 0) {
      return response()->json(['error' => 'cannot modify', 'messages' => "time off has " . $pending_time_offs . " existing request(s)"], 400);
    }

    \DB::beginTransaction();
    try {
      $time_off->fill($request->all());
      $time_off->updated_by = $this->me->id;
      $time_off->save();

      $this->log_user_action(
        Carbon::parse($time_off->updated_at)->toDateString(),
        Carbon::parse($time_off->updated_at)->toTimeString(),
        $this->me->id,
        $this->me->name,
        "Updated " . $time_off->time_off_type . " as Time Off Type",
        "HR & Payroll"
      );

      \DB::commit();
      $time_off->loadMissing('color');
      return response()->json($time_off);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }

  public function list(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }

    $query = \App\TimeOff::with('color');

    $ALLOWED_FILTERS = ['is_active'];
    $SEARCH_FIELDS = [];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

    return response()->json($response);
  }

  public function read (Request $request, \App\TimeOff $time_off) {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }
    $time_off->loadMissing('color');
    return response()->json($time_off);
  }

  public function set_status(Request $request, \App\TimeOff $time_off)
  {
    $unauthorized = $this->is_not_authorized(['toggle_time_off']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $validator_arr = [
      'status' => 'required'
    ];

    $validator = Validator::make($request->all(), $validator_arr);

    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }

    $pending_time_offs = \App\TimeOffRequest::where('time_off_requests.status', 0)
      ->join('time_off_balance', 'time_off_balance.id', 'time_off_requests.time_off_balance_id')
      ->where('time_off_balance.time_off_id', $time_off->id)
      ->count();

    if ($pending_time_offs > 0 && !$request->input('status')) {
      return response()->json(['error' => 'cannot deactivate', 'messages' => "time off has " . $pending_time_offs . " existing request(s)"], 400);
    }

    \DB::beginTransaction();
    try {
      $time_off->is_active = request('status');
      $time_off->updated_by = $this->me->id;
      $time_off->save();

      $this->log_user_action(
        Carbon::now(),
        Carbon::now(),
        $this->me->id,
        $this->me->name,
        $time_off->status == true ? "Activated Time Off " . $time_off->time_off_type : "Deactivated Time Off " . $time_off->time_off_type,
        "HR & Payroll"
      );
      \DB::commit();
      return response()->json($time_off);
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
  }

  public function time_off_color_list (Request $request) {
    $validator_arr = [
      'whitelist' => 'sometimes|array',
      'whitelist.*' => 'exists:time_off_color,id',
      'q' => 'sometimes|string'
    ];
    $validator = Validator::make($request->all(), $validator_arr);
    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }

    $colors = \App\TimeOffColor::query()
    ->whereNotIn('id', function ($query) {
      $query->select('time_off_color_id')->from('time_offs');
    })
    ->orWhereIn('id', $request->input('whitelist', []))
    ->get();

    return response()->json($colors);
  }
}
