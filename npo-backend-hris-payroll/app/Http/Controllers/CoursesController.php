<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class CoursesController extends Controller
{
    public function create_or_update(Request $request, \App\Courses $course, $is_new = false)
    {

        $validator_arr = [
            'course_type' => 'required',
        ];

        if ($is_new) {
            $validator_arr['course_name'] = 'required|unique:courses';
        } else {
            $validator_arr['course_name'] = 'required';
        }

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        //required fields
        $course->course_name = request('course_name');
        $course->course_type = request('course_type');

        \DB::beginTransaction();
        try {
            if ($is_new) {
                $course->save();
            } else {
                $course->save();
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        \DB::commit();

        return response()->json($course);
    }

    public function create(Request $request)
    {
        // $unauthorized = $this->is_not_authorized(['create_courses']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        return $this->create_or_update($request, new \App\Courses(), true);
    }

    public function update(Request $request, \App\Courses $course)
    {
        // $unauthorized = $this->is_not_authorized(['edit_courses']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        return $this->create_or_update($request, $course);
    }

    public function read(Request $request, \App\Courses $course)
    {
        // $unauthorized = $this->is_not_authorized(['view_courses']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        return response()->json($course);
    }

    public function list(Request $request)
    {
        // $unauthorized = $this->is_not_authorized(['view_courses']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }
        $query = \App\Courses::select('*');

        // filtering
        $ALLOWED_FILTERS = ['status', 'course_type',];
        $SEARCH_FIELDS = ['course_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function set_status(Request $request, \App\Courses $course)
    {
        // $unauthorized = $this->is_not_authorized(['edit_courses']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }

        $validator_arr = [
            'status' => 'required'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        //course fields
        $adjustment->status = request('status');

        \DB::beginTransaction();
        try {
            $course->save();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        \DB::commit();

        return response()->json($course);
    }
}
