<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use DateTime;
use Tymon\JWTAuth\Exceptions\JWTException;

class WorkScheduleController extends Controller
{
  public function create_or_update(Request $request, \App\WorkSchedule $work_schedule, $is_new = false)
  {
    if ($is_new) {
      $validator_arr = [
        'work_schedule_name' => 'required|unique:work_schedules',
        'time_option' => 'required',
        'time_option_details' => 'required',
        'breaks' => 'required'
      ];
    } else {
      $validator_arr = [
        'work_schedule_name' => "unique:work_schedules,work_schedule_name,$work_schedule->id",
      ];
    }


    //validation for time_option 1 = fixed daily hours
    if (request('time_option') == 1) {
      array_merge($validator_arr, [
        'monday' => 'required',
        'tuesday' => 'required',
        'wednesday' => 'required',
        'thursday' => 'required',
        'friday' => 'required',
        'saturday' => 'required',
        'sunday' => 'required'
      ]);


      $validator = Validator::make(array_merge($request->all(), $request->only('time_option_details')['time_option_details']), $validator_arr);

      if ($validator->fails()) {
        return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
      }

      //validation for time_option 2 = fixed daily times
    } else if (request('time_option') == 4) {
      array_merge($validator_arr, []);

      $validator_inc = array_merge($request->all(), $request->only('time_option_details')['time_option_details']);

      foreach ($request->only('time_option_details')['time_option_details'] as $day => $details) {

        $validator = Validator::make(array_merge($validator_inc, $details), $validator_arr);

        if ($validator->fails()) {
          return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }
      }

      //validation for time_option 3 = flexible weekly hours
    } else if (request('time_option') == 2) {
      array_merge($validator_arr, [
        'monday' => 'required',
        'tuesday' => 'required',
        'wednesday' => 'required',
        'thursday' => 'required',
        'friday' => 'required',
        'saturday' => 'required',
        'sunday' => 'required',
        'start_time' => 'required',
        'end_time' => 'required',
        'grace_period' => 'required'
      ]);

      $validator_inc = array_merge($request->all(), $request->only('time_option_details')['time_option_details']);
      foreach ($request->only('time_option_details')['time_option_details'] as $day => $details) {
        $validator = Validator::make(array_merge($validator_inc, $details), $validator_arr);
        if ($validator->fails()) {
          return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }
      }

      //validation for time_option 3 = flexible weekly hours
    } else {
      $validator_arr['flexible_weekly_hours'] = 'required';

      $validator = Validator::make(array_merge($request->all(), $request->only('time_option_details')['time_option_details']), $validator_arr);

      if ($validator->fails()) {
        return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
      }
    }

    $work_schedule->work_schedule_name = request('work_schedule_name');
    $work_schedule->time_option = request('time_option');
    if (array_key_exists('flexible_weekly_hours', request('time_option_details'))) {
      $work_schedule->flexible_weekly_hours = request('time_option_details')['flexible_weekly_hours'];
    }

    $new_status = request('status', true);
    $employees_assigned_to_the_work_schedule = \DB::table('employment_and_compensation')
      ->where('work_schedule_id', $work_schedule->id)
      ->get();
    $are_employees_assigned_to_the_work_schedule = count($employees_assigned_to_the_work_schedule) > 0;


    // attach to response to let FE know
    // if deactivating failed
    $has_failed_to_deactivate = false;

    // (1) if its not new, check if employees are assigned
    // to the work schedule
    // (2) if status is false, it needs checking before
    // setting the new_status
    // (3) check if one or more employees meet the query
    if (
      !$is_new
      && $new_status === false
      && $are_employees_assigned_to_the_work_schedule
    ) {
      $has_failed_to_deactivate = true;
      $work_schedule->is_active = $work_schedule->is_active ? true : false;

      // dont change $work_schedule->is_active
    } else {
      $work_schedule->is_active = $new_status;
    }

    // validation fot creating breaks in before saving work schedule

    // foreach($request->only('breaks')['breaks'] as $break_payload){
    //     $break_time = new \App\BreakTime();

    //     $validator_arr = [
    //         'name' => 'required',
    //         'type' => 'required',
    //         'minutes' => 'required_if:type,1',
    //         'period' => 'required_if:type,1',
    //         'start_time' => 'required_if:type,0',
    //         'end_time' => 'required_if:type,0',
    //     ];


    //     $validator = Validator::make($break_payload, $validator_arr);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
    //     }
    // }

    // end validation

    \DB::beginTransaction();
    try {
      if ($is_new) {
        $work_schedule->created_by = $this->me->id;
        $work_schedule->updated_by = $this->me->id;
        $work_schedule->save();
        //$result = 'created';
      } else {
        $work_schedule->updated_by = $this->me->id;
        $work_schedule->save();
        //$result = 'updated';
      }

      //for time_option = 1, add entry to fixed_daily_hours
      if ($work_schedule->time_option == 1) {
        $timeDetails = $request->only('time_option_details')['time_option_details'];
        $time_option_details = $this->saveFixedDailyHours($is_new, $timeDetails, $work_schedule);
        //for time_option = 2, add entry to fixed_daily_times
      } else if ($work_schedule->time_option == 2) {
        if ($is_new) {
          $fixed_daily_times = new \App\FixedDailyTimes;
          $fixed_daily_times->work_schedule_id = $work_schedule->id;
        } else {
          $fixed_daily_times = \App\FixedDailyTimes::where('work_schedule_id', $work_schedule->id)
            ->first();
          // check if query returned anything
          if (!$fixed_daily_times) {
            // incase if the query returned nothing
            $fixed_daily_times = new \App\FixedDailyTimes;
            $fixed_daily_times->work_schedule_id = $work_schedule->id;
          }
        }
        $breaKDuration = $this->subtractTime(request('breaks')[0]['start_time'], request('breaks')[0]['end_time']);
        foreach ($request->only('time_option_details')['time_option_details'] as $day => $details) {
          $start_times[$day] = $details['start_time'];
          $end_times[$day] = $details['end_time'];
          $end_times_is_next_day[$day] = $details['end_time_is_next_day'];
          $grace_periods[$day] = $details['grace_period'];
          $diff = $this->subtractTime($details['start_time'], $details['end_time']) - $breaKDuration;

          if ($details['start_time'] && $details['end_time']) {
            $startTimeTemp = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now()->toDateString() . " " . $details['start_time']);
            $endTimeTemp = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now()->toDateString() . " " . $details['end_time']);
            if ($end_times_is_next_day[$day]) {
              $endTimeTemp->add(1, 'days');
            }
            if ($startTimeTemp->gte($endTimeTemp)) {
              throw new \Exception("Error on $day. END TIME must be after START TIME");
            }
          }
        }
        $fixed_daily_times->start_times = $start_times;
        $fixed_daily_times->end_times = $end_times;
        $fixed_daily_times->end_times_is_next_day = $end_times_is_next_day;
        $fixed_daily_times->grace_periods = $grace_periods;
        $fixed_daily_times->save();
        foreach ($request->only('time_option_details')['time_option_details'] as $day => $details) {
          $days[$day] = $details;
        }
        $time_option_details = $days;

        //for time_option = 3
      } else {
        $time_option_details['flexible_weekly_hours'] = $work_schedule->flexible_weekly_hours;
      }

      // create breaks here ! validation if missing field
      if (request('time_option') !== 4) {

        if ($work_schedule->id) {
          $request->request->add(['work_schedule_id' => $work_schedule->id]);
          $adder_response = $this->create_break($request, $is_new, $work_schedule);
          if ($adder_response == null) {
            \App\WorkSchedule::where('id', $work_schedule->id)->delete();
            return response()->json(array("error" => "check create breaks! "), 400);
          }
        }
      }
      \DB::commit();

      $this->log_user_action(
        Carbon::parse($work_schedule->created_at)->toDateString(),
        Carbon::parse($work_schedule->created_at)->toTimeString(),
        $this->me->id,
        $this->me->name,
        "Created " . $work_schedule->work_schedule_name . " as Work Schedule",
        "HR & Payroll"
      );
  
      $query = \DB::table('break_times')
        ->where('work_schedule_id', '=', $work_schedule->id)
        ->get();
  
  
      $count_assigned = \DB::table('employment_and_compensation')
        ->where('work_schedule_id', $work_schedule->id)
        ->count();
  
  
      return response()->json(array(
        'id' => $work_schedule->id,
        'status' => $work_schedule->is_active,
        'work_schedule_name' => $work_schedule->work_schedule_name,
        'time_option' => $work_schedule->time_option,
        'time_option_details' => $time_option_details,
        'breaks' => $query,
        'created_at' => $work_schedule->created_at,
        'updated_at' => $work_schedule->updated_at,
        'has_failed_to_deactivate' => $has_failed_to_deactivate,
        'assigned' => $count_assigned
      ));
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }

    // if(!$is_new){
    //     $breaks = \App\BreakTime::where('work_schedule_id', '=', $work_schedule->id)->get();
    //     foreach($breaks as $item){
    //         $item->delete();
    //     }
    // }

    // $request->request->add(['work_schedule_id' => $work_schedule->id]);
    // $adder_response = $this->create_break($request);
    // if($adder_response != null) return $adder_response;
  }

  private function subtractTime($start_time, $end_time)
  {
    $startTime = Carbon::parse($start_time);
    $endTime = Carbon::parse(($end_time));
    return $startTime->diffInHours($endTime);
  }

  private function saveFixedDailyHours($is_new, $timeDetails, $work_schedule)
  {
    if ($is_new) {
      $fixed_daily_hours = new \App\FixedDailyHours;
      $fixed_daily_hours->work_schedule_id = $work_schedule->id;
    } else {
      $fixed_daily_hours = \App\FixedDailyHours::where('work_schedule_id', $work_schedule->id)
        ->first();
    }
    $fixed_daily_hours->daily_hours = $timeDetails;
    $fixed_daily_hours->save();

    return $fixed_daily_hours->daily_hours;
  }

  public function create(Request $request)
  {
    $unauthorized = $this->is_not_authorized(['create_work_schedule']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, new \App\WorkSchedule(), true);
  }

  public function update(Request $request, \App\WorkSchedule $work_schedule)
  {
    $unauthorized = $this->is_not_authorized(['edit_work_schedule']);
    if ($unauthorized) {
      return $unauthorized;
    }
    return $this->create_or_update($request, $work_schedule);
  }

  public function read(Request $request, \App\WorkSchedule $work_schedule)
  {
    $unauthorized = $this->is_not_authorized(['view_work_schedules']);
    if ($unauthorized) {
      return $unauthorized;
    }

    $item['id'] = $work_schedule->id;
    $item['work_schedule_name'] = $work_schedule->work_schedule_name;
    $item['time_option'] = $work_schedule->time_option;

    if ($item['time_option'] == 1) {
      $query = \DB::table('fixed_daily_hours')
        ->where('work_schedule_id', '=', $work_schedule->id)
        ->get()
        ->first();

      $daily_hours = json_decode($query->daily_hours, true);
      foreach ($daily_hours as $daily_hour => $details) {
        $time_option_details[$daily_hour] = $details;
      }
      $item['time_option_details'] = $time_option_details;
    } else if ($item['time_option'] == 2) {
      $query = \DB::table('fixed_daily_times')
        ->where('work_schedule_id', '=', $work_schedule->id)
        ->get()
        ->first();

      $start_times = json_decode($query->start_times, true);
      $end_times = json_decode($query->end_times, true);
      $end_times_is_next_day = json_decode($query->end_times_is_next_day, true);
      $grace_periods = json_decode($query->grace_periods, true);
      $keys = array_keys($grace_periods);
      foreach ($keys as $key => $value) {
        $time_option_details[$value] = array();

        $time_option_details[$value]['start_time'] = $start_times[$value];
        $time_option_details[$value]['end_time'] = $end_times[$value];
        $time_option_details[$value]['end_time_is_next_day'] = $end_times_is_next_day[$value];
        $time_option_details[$value]['grace_period'] = $grace_periods[$value];
      }
      $item['time_option_details'] = $time_option_details;
    } else if ($item['time_option'] == 3) {
      $item['time_option_details'] = ['hours' => $work_schedule->flexible_weekly_hours];
    }

    $item['breaks'] = \DB::table('break_times')
      ->where('work_schedule_id', '=', $work_schedule->id)
      ->get();
    $item['status'] = $work_schedule->is_active == 1 ? true : false;
    $item['created_at'] = Carbon::parse($work_schedule->created_at)->format('Y-m-d H:i:s');
    $item['updated_at'] = Carbon::parse($work_schedule->updated_at)->format('Y-m-d H:i:s');

    return response()->json($item);
  }

  public function list(Request $request)
  {
    $unauthorized = $this->is_not_authorized();
    if ($unauthorized) {
      return $unauthorized;
    }

    $query = \App\WorkSchedule::with([
      'breaks',
      'fixed_daily_hours',
      'fixed_daily_times',
    ])
      ->withCount('assigned_employees');

    $ALLOWED_FILTERS = ['is_active'];
    $SEARCH_FIELDS = ['work_schedule_name'];
    $JSON_FIELDS = [];
    $BOOL_FIELDS = [];
    $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

    return response()->json($response);
  }

  public function set_status(Request $request, \App\WorkSchedule $work_schedule)
  {
    $unauthorized = $this->is_not_authorized(['toggle_work_schedule']);
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

    //required fields
    $work_schedule->is_active = request('status');
    $table = \DB::table('employment_and_compensation')->get();

    \DB::beginTransaction();
    try {
      for ($i = 0; $i < count($table); $i++) {
        if (request('status') === false && $table[$i]->work_schedule_id === $work_schedule->id) {
          return response()->json(array("error" => "Deactivate work schedule failed!", "message" => "Sorry! There are employees with this work schedule."));
        } else {
          $work_schedule->updated_by = $this->me->id;
          $work_schedule->save();
        }
      }
      //$result = 'updated';
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
    \DB::commit();

    $this->log_user_action(
      Carbon::now(),
      Carbon::now(),
      $this->me->id,
      $this->me->name,
      $work_schedule->status == true ? "Activated Work Schedule " . $work_schedule->work_schedule_name : "Deactivated Work Schedule " . $work_schedule->work_schedule_name,
      "HR & Payroll"
    );

    return response()->json($work_schedule);
  }


  public function create_break(Request $request, $is_new, $work_schedule)
  {

    if (!$is_new && count($request->only('breaks')['breaks']) > 0) {
      $breaks = \App\BreakTime::where('work_schedule_id', '=', $work_schedule->id)->get();
      foreach ($breaks as $item) {
        $item->delete();
      }
    }


    foreach ($request->only('breaks')['breaks'] as $break_payload) {
      $break_time = new \App\BreakTime();

      $request_arr = array_merge(
        $request->only('work_schedule_id'),
        $break_payload
      );

      $break_time->fill(
        $request_arr
      );

      //if type 1 - start_time and end_time is null, else if type 2 - fminutes is null
      if ($break_payload['type'] == false) $break_time->minutes = null;
      else if ($break_payload['type'] == true) {
        $break_time->start_time = null;
        $break_time->end_time = null;
      }

      if (array_key_exists('status', $break_payload)) {
        $break_time->status = $break_payload['status'];
      } else {
        $break_time->status = true;
      }

      // TODO: save
      \DB::beginTransaction();
      try {
        $break_time->save();
        $result = "Break Time has been added";
      } catch (\Exception $e) {
        \DB::rollBack();
        throw $e;
      }
      \DB::commit();
    }

    if ($is_new) {
      return $break_time->id;
    } else {
      return response()->json(array("Update" => "Success!"));
    }
  }


  public function breaks_set_status(Request $request, \App\WorkSchedule $work_schedule)
  {
    $unauthorized = $this->is_not_authorized();
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

    $break_time = \DB::table('break_times')->where('break_times.work_schedule_id', $work_schedule->id);
    //required fields
    $break_time->status = request('status');
    // $table = \DB::table('break_times')->pluck('status');

    \DB::beginTransaction();
    try {
      // for ($i = 0; $i < count($table); $i++){
      //     if($table[$i] === 1){
      //         return response()->json(array("error" => "deactive work break failed!", "message" => "Sorry! There are employees with this Break schedule."), 400);
      //     } else {
      $break_time->updated_by = $this->me->id;
      $break_time->save();
      //     }
      // }
      //$result = 'updated';
    } catch (\Exception $e) {
      \DB::rollBack();
      throw $e;
    }
    \DB::commit();

    $this->log_user_action(
      Carbon::now(),
      Carbon::now(),
      $this->me->id,
      $this->me->name,
      $work_schedule->status == true ? "Activated Work Schedule " . $work_schedule->work_schedule_name : "Deactivated Work Schedule " . $work_schedule->work_schedule_name,
      "HR & Payroll"
    );

    return response()->json($work_schedule);
  }
}
