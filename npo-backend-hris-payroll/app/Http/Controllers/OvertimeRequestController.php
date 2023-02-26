<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class OvertimeRequestController extends Controller
{
    public function submit_overtime_requests(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'overtime_requests' => 'required|array',
            'overtime_requests.*' => 'exists:overtime_requests,id,status,-2',
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            \App\OvertimeRequest::whereIn('id', $request->input('overtime_requests'))
                ->update(['status' => 0, 'date_submitted' => Carbon::now()]);
            $overtime_requests = \App\OvertimeRequest::whereIn('id', $request->input('overtime_requests'))->get();

            foreach ($overtime_requests as $overtime_request) {
                \App\Notification::create_hr_notification(
                    ['view_overtime_request', 'approve_overtime_request'],
                    $this->me->name . ' submitted an Overtime on ' . Carbon::parse($overtime_request->start_time)->format('Y-m-d'),
                    \App\Notification::NOTIFICATION_SOURCE_OVERTIME,
                    $overtime_request->id,
                    $overtime_request
                );
            }

            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $request->input('overtime_requests')));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => $e->getMessage()
            ), 400);
        }
    }

    public function list_employee_ot_history(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\OvertimeRequest::where([
            ['overtime_requests.employee_id', $this->me->employee_details->id],
            ['overtime_requests.status', '!=', '0']
        ]);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_employee_ot(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized(['view_overtime_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\OvertimeRequest::with([
            'approvers' => function ($q) {
                $q->select('approval_item_employee.id as approval_item_employee_id', 'can_approve', 'remarks', 'attachments', 'approval_item_id', 'approver_id', 'approval_item_employee.updated_at');
            },
            'approvers.approver' => function ($q) {
                $q->select('id', 'users_id');
            },
            'approvers.approver.personal_information' => function ($q) {
                $q->select('employee_id', 'first_name', 'last_name', 'middle_name');
            },
            'approvers.approval_level' => function ($q) {
                $q->select('description');
            },
            'approvers.approval_item' => function ($q) {
                $q->select('id', 'status', 'updated_at');
            },
            'authority_to_ot',
        ])
            ->where('overtime_requests.employee_id', !$employee_id ? $this->me->employee_details->id : $employee_id);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_time', [$request->input('start_date'), $request->input('end_date')]);
            $query->whereBetween('end_time', [$request->input('start_date'), $request->input('end_date')]);
        }

        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_employee_ot_self(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\OvertimeRequest::with([
            'approvers' => function ($q) {
                $q->select('approval_item_employee.id as approval_item_employee_id', 'can_approve', 'remarks', 'attachments', 'approval_item_id', 'approver_id', 'approval_item_employee.updated_at');
            },
            'approvers.approver' => function ($q) {
                $q->select('id', 'users_id');
            },
            'approvers.approver.personal_information' => function ($q) {
                $q->select('employee_id', 'first_name', 'last_name', 'middle_name');
            },
            'approvers.approval_level' => function ($q) {
                $q->select('description');
            },
            'approvers.approval_item' => function ($q) {
                $q->select('id', 'status', 'updated_at');
            },
            'authority_to_ot'
        ])
            ->where('overtime_requests.employee_id', $this->me->employee_details->id);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_time', [$request->input('start_date'), $request->input('end_date')]);
            $query->whereBetween('end_time', [$request->input('start_date'), $request->input('end_date')]);
        }

        $readyQuery = clone $query;
        $ready = $readyQuery->where('status', -2)->count();

        $pendingQuery = clone $query;
        $pending = $pendingQuery->where('status', 0)->count();

        if ($request->filled('status')) {
            $query->whereIn('status', $request->input('status'));
        }

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        $response['total_ready'] = $ready;
        $response['total_pending'] = $pending;
        return response()->json($response);
    }

    public function list_requests_for_approver(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_overtime_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $query = \App\OvertimeRequest::with([
            'approvers' => function ($q) {
                $q->select('approval_item_employee.id as approval_item_employee_id', 'can_approve', 'remarks', 'attachments', 'approval_item_id', 'approver_id', 'approval_item_employee.updated_at');
            },
            'approvers.approver' => function ($q) {
                $q->select('id', 'users_id');
            },
            'approvers.approver.personal_information' => function ($q) {
                $q->select('employee_id', 'first_name', 'last_name', 'middle_name');
            },
            'approvers.approval_level' => function ($q) {
                $q->select('description');
            },
            'approvers.approval_item' => function ($q) {
                $q->select('id', 'status', 'updated_at');
            },
            'authority_to_ot'
        ])
            ->joinSub($approver_list_query, 'approvers', function ($join) {
                $join->on('overtime_requests.approval_request_id', '=', 'approvers.approval_request_id');
            })
            ->leftJoin('employment_and_compensation', 'overtime_requests.employee_id', 'employment_and_compensation.employee_id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('personal_information', 'overtime_requests.employee_id', 'personal_information.employee_id')
            ->where('approvers.can_approve', '=', '1')
            ->select(
                'overtime_requests.*',
                'approvers.approval_date',
                \DB::raw('CONCAT(
            IFNULL(personal_information.last_name, \'\'),
                \', \',
                IFNULL(personal_information.first_name, \'\'),
                \' \',
                IFNULL(personal_information.middle_name, \'\')
            ) as name'),
                'departments.department_name'
            );

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_time', [$request->input('start_date'), $request->input('end_date')]);
            $query->whereBetween('end_time', [$request->input('start_date'), $request->input('end_date')]);
        }

        // filtering
        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        return response()->json($response);
    }

    public function approve_reject_request(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['approve_overtime_request']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'overtime_requests' => 'required|array',
            'overtime_requests.*' => 'exists:overtime_requests,id',
            'status' => 'required|in:-1,1'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $result_overtime_requests = array();
            foreach ($request->input('overtime_requests') as $overtime_requests_id) {
                $overtime_request = \App\OvertimeRequest::find($overtime_requests_id);
                if ($overtime_request->status !== 0) {
                    throw new \Exception('Overtime request is not pending');
                }
                $result = app('\App\Http\Controllers\AppFlowController')->approve_reject_request($overtime_request->approval_request_id, $this->me->employee_details->id, $request->input('status'));
                if ($result === 'request_approved' || $result === 'request_rejected') {
                    $overtime_request->status = $result === 'request_approved' ? 1 : -1;
                    $overtime_request->save();
                    if ($result === 'request_approved') {
                        $this->add_offset($overtime_request);
                    }
                    $employee = \App\Employee::where('id', $overtime_request->employee_id)->first();
                    \App\Notification::create_user_notification(
                        $employee->users_id,
                        'Your Overtime on ' . Carbon::parse($overtime_request->start_time)->format('Y-m-d') . ' is ' . ($result === 'request_approved' ? 'Approved' : 'Declined'),
                        \App\Notification::NOTIFICATION_SOURCE_OVERTIME,
                        $overtime_request->id,
                        $overtime_request
                    );
                }
                array_push($result_overtime_requests, array(
                    'id' => $overtime_request->id,
                    'status' => $overtime_request->status,
                ));
            }
            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $result_overtime_requests));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function get_available_overtimes_self(Request $request) {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        return $this->list_available_overtimes($request, $this->me->employee_details->id);
    }

    public function get_available_overtimes(Request $request, \App\Employee $employee) {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        return $this->list_available_overtimes($request, $employee->id);
    }

    private function list_available_overtimes(Request $request, $employee_id) {
        $query = \App\OvertimeRequest::query()
        ->where([
            ['employee_id', $employee_id],
            ['status', 1]
        ])
        ->leftJoin('overtime_uses', 'overtime_uses.overtime_request_id', 'overtime_requests.id')
        ->select(
            'overtime_requests.*',
            \DB::raw('
                CAST(
                GREATEST(
                    overtime_requests.duration_in_minutes - COALESCE(SUM(overtime_uses.duration_in_minutes), 0),
                    0
                ) as UNSIGNED) as available_minutes
            ')
        )
        ->havingRaw('
            CAST(
            GREATEST(
                overtime_requests.duration_in_minutes - COALESCE(SUM(overtime_uses.duration_in_minutes), 0),
                0
            ) as UNSIGNED) > 0
        ')
        ->groupBy('id');

        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear   = $startOfYear->copy()->endOfYear();

        $query->whereBetween('start_time', [$startOfYear, $endOfYear]);
        $query->whereBetween('end_time', [$startOfYear, $endOfYear]);

        return response()->json($query->get());
    }

    private function add_offset($overtime_request)
    {
        $timeoff_balance = \App\TimeOffBalance::where([
            ['employee_id', $overtime_request->employee_id],
            ['time_off_id', 5]
        ])->first();

        if (!$timeoff_balance) {
            throw new \Exception("Employee does not have a CTO leave.");
        }

        $overtime_minutes = $overtime_request->duration_in_minutes ?? 0;
        $overtime_hours = bcdiv($overtime_minutes, 60, 3);

        \App\TimeOffAdjustment::create([
            'time_off_balance_id' => $timeoff_balance->id,
            'adjustment_value' => $overtime_hours,
            'effectivity_date' => Carbon::now()->toDateString(),
            'remarks' => 'Overtime on ' . Carbon::createFromFormat('Y-m-d H:i:s', $overtime_request->start_time)->toDateString()
        ]);
    }

    private function getTimeDIff($startTime, $endTime)
    {
        $start_time = Carbon::parse($startTime);
        $end_time = Carbon::parse($endTime);
        $hourDiff = $end_time->hour - $start_time->hour;
        $minDiff = $end_time->minute - $start_time->minute;
        return $hourDiff + ($minDiff / 60);
    }
}
