<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class HolidayController extends Controller
{
  public function create_or_update(Request $request, \App\Holiday $holiday, $is_new = false)
  {
    if ($is_new == true) {
      $validator_arr = [
        'holiday_name' => 'required|unique:holidays',
        'time_data_id' => 'required',
        'date' => 'required'
      ];
    } else {
      $validator_arr = [
        //'holiday_name' => 'required|unique:holidays',
        'time_data_id' => 'required',
        'date' => 'required'
      ];
    }

    $validator = Validator::make($request->all(), $validator_arr);

    if ($validator->fails()) {
      return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    }

    $holiday->fill(
      $request->only(['holiday_name', 'time_data_id', 'is_recurring'])
    );

    $holiday->date = Carbon::parse(request('date'))->toDateString();

    \DB::beginTransaction();
    try {
      if ($is_new) {
        $holiday->created_by = $this->me->id;
        $holiday->updated_by = $this->me->id;
        $holiday->save();
      } else {
        $holiday->updated_by = $this->me->id;
        $holiday->save();
      }
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
    \DB::commit();

    if ($is_new) {

      $this->log_user_action(
        Carbon::parse($holiday->created_at)->toDateString(),
        Carbon::parse($holiday->created_at)->toTimeString(),
        $this->me->id,
        $this->me->name,
        "Created " . $holiday->holiday_name . " as Holiday",
        "HR & Payroll"
      );

      return $this->read($holiday->id);
    } else {
      $this->log_user_action(
        Carbon::parse($holiday->created_at)->toDateString(),
        Carbon::parse($holiday->updated_at)->toTimeString(),
        $this->me->id,
        $this->me->name,
        "Updated " . $holiday->holiday_name . " as Holiday",
        "HR & Payroll"
      );

      return $this->read($holiday->id);
    }
  }

  public function enrich($_tmodel)
  {
    // TODO
    return $_tmodel;
  }

  public function create(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['create_holiday']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, new \App\Holiday(), true);
  }

  public function read($holiday)
  {
    $unauthorized = $this->is_not_authorized(['view_holidays']);
    if ($unauthorized) {
      return $unauthorized;
    }

    // $query = \DB::table('holidays')->where('holidays.id', '=', $holiday)
    //             ->get();

    // $result = [];
    // foreach($query as $row){
    //     $array = [];
    //     $array['id'] = $row->id;
    //     $array['holiday_name'] = $row->holiday_name;
    //     $array['time_data_id'] = $row->time_data_id;
    //     $array['time_data'] = \DB::table('time_data')->where('id', '=', $row->time_data_id)->select('time_data_name')->first();
    //     $array['date'] = $row->date;
    //     $array['created_by'] = $row->created_by;
    //     $array['updated_by'] = $row->updated_by;
    //     $array['created_at'] = $row->created_at;
    //     $array['updated_at'] = $row->updated_at;

    //     // array_push($result, $array);
    // }

    return response()->json($holiday);
  }

  public function update(Request $request, \App\Holiday $holiday)
  {
    $unauthorized = $this->is_not_authorized(['edit_holiday']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, $holiday);
  }

  public function list(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }

    $query = \App\Holiday::with('time_data');

    if ($request->filled('time_data_name')) {
      $query->whereHas('time_data', function ($q) use ($request) {
        $q->where('time_data_name', $request->input('time_data_name'));
      });
    }

    // filtering
    $ALLOWED_FILTERS = [];
    $SEARCH_FIELDS = [];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

    return response()->json($response);
  }

  public function delete(Request $request, \App\_TModel $_tmodel)
  {
    $unauthorized = $this->is_not_authorized(['delete_holiday']);
    if ($unauthorized) {
      return $unauthorized;
    }
    $_tmodel->delete();
    return response()->json(array('_tmodel' => $_tmodel, 'result' => 'deleted'));
  }
}
