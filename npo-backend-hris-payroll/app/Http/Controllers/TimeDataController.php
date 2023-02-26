<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class TimeDataController extends Controller
{

    public function create_or_update(Request $request, \App\TimeData $time_data, $is_new = false)
    {

        $validator_arr = [
            'time_data_name' => 'required|unique:time_data,time_data_name,' . $time_data->id,
            'multiplier' => 'required'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        //required fields
        $time_data->time_data_name = request('time_data_name');
        $time_data->multiplier = request('multiplier');

        //not required fields
        $time_data->status = request('status', 1);


        // TODO: save
        \DB::beginTransaction();
        try {
            if ($is_new) {
                $time_data->created_by = $this->me->id;
                $time_data->updated_by = $this->me->id;
                $time_data->status = 1; //default
                $time_data->save();
            } else {
                $time_data->updated_by = $this->me->id;
                $time_data->save();
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        \DB::commit();

        // $this->insertHistory($this->me->id, 'ADD TIME DATA', null, null, null, null);
        // $time_data= \App\TimeData::where('id', '=' , $time_data->id)->first();
        return response()->json($time_data);
    }

    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['create_time_data']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, new \App\TimeData(), true);
    }

    public function update(Request $request, \App\TimeData $time_data)
    {
        $unauthorized = $this->is_not_authorized(['edit_time_data']);
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, $time_data);
    }

    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\TimeData::select('*');

        // filtering
        $ALLOWED_FILTERS = ['time_data_name', 'multiplier'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function read(\App\TimeData $time_data)
    {
        $unauthorized = $this->is_not_authorized(['view_time_data']);
        if ($unauthorized) {
            return $unauthorized;
        }

        return response()->json($time_data);
    }

    public function set_status(Request $request, \App\TimeData $time_data)
    {
        $unauthorized = $this->is_not_authorized(['toggle_time_data']);
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
        $time_data->status = request('status');

        \DB::beginTransaction();
        try {
            $time_data->save();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        \DB::commit();

        return response()->json($time_data);
    }
}
