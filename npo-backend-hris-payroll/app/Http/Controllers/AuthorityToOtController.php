<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthorityToOtController extends Controller
{
    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['request_authority_to_overtime']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'requested_by' => 'required',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date:Y-m-d',
            'employees' => 'required|array'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $authority_to_ot = \App\AuthorityToOt::create([
                'requested_by' => $request->input('requested_by'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'requested_by' => $request->input('requested_by'),
                'created_by' => $this->me->employee_details->id
            ]);
            if ($request->filled('remarks')) {
                $authority_to_ot->remarks = $request->input('remarks');
            }
            $authority_to_ot->save();

            $this->save_overtime_requests($authority_to_ot->id, $request->input('employees'));
            \DB::commit();
            $authority_to_ot->loadMissing('overtime_requests');
            return response()->json(['result' => 'Success', 'data' => $authority_to_ot], 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'validation_failed', 'messages' => $e->getMessage()], 400);
        }

        // return response()->json(['error' => 'validation_failed', 'messages' => $request->all()], 400);
    }

    private function save_overtime_requests($authority_to_ot_id, $employees)
    {
        foreach ($employees as $employee) {
            $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('employee_ot', $employee['employee_id']);
            if ($app_flow_id === -1) {
                throw new \Exception('no approval flow exists');
            }
            foreach ($employee['schedule'] as $schedule) {
                $overtime_request = \App\OvertimeRequest::create([
                    'employee_id' => $employee['employee_id'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'dtr_date' => $schedule['date']
                ]);
                $overtime_request->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                    ->create_approval_flow_for_request($this->me->id, $employee['employee_id'], $app_flow_id, 'employee_ot');
                $overtime_request->status = -3;
                $overtime_request->authority_to_ot_id = $authority_to_ot_id;
                $overtime_request->save();

                $employeeData = \App\Employee::where('id',$employee['employee_id'])->first();
                \App\Notification::create_user_notification(
                    $employeeData->users_id,
                    'You have a Request for Authority to Overtime on ' . Carbon::parse($schedule['start_time'])->format('Y-m-d'),
                    \App\Notification::NOTIFICATION_SOURCE_RAO,
                    $overtime_request->id,
                    $overtime_request
                );
            }
        }
    }

    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_request_authority_to_overtime']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\AuthorityToOt::with('overtime_requests');

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    private function get_dates_from_schedules($schedules) {
        $start_date = null;
        $end_date = null;
        foreach ($schedules as $schedule) {
            $date = Carbon::parse($schedule['start_time']);
            if ($start_date == null || $start_date > $date) {
                $start_date = $date;
            }
            if ($end_date == null || $end_date < $date) {
                $end_date = $date;
            }
        }
        if ($end_date == $start_date) {
            return $start_date->format('Y-m-d');
        }
        else {
            return $start_date->format('Y-m-d') . ' to ' . $end_date->format('Y-m-d');
        }
    }

}
