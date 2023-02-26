<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ContributionRequestController extends Controller
{
    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'contribution_type' => 'required',
            'amount' => 'required'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('employee_contribution', $this->me->employee_details->id);
        if ($app_flow_id === -1) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
        }

        \DB::beginTransaction();
        try {
            $contribution_request = \App\ContributionRequest::create([
                'contribution_type' => $request->input('contribution_type'),
                'employee_id' => $this->me->employee_details->id,
                'amount' => $request->input('amount'),
                'remarks' => $request->input('remarks')
            ]);
            $contribution_request->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'employee_contribution');
            $contribution_request->save();
            \App\Notification::create_hr_notification(
                ['view_contribution_requests', 'approve_contribution_request', 'monitor_employee_contributions', 'view_remittance'],
                $this->me->name . ' requested to adjust '
                    //. $contribution_request->contribution_type
                    . 'Pag Ibig Contribution'
                    ,
                \App\Notification::NOTIFICATION_SOURCE_CONTRIBUTION,
                $contribution_request->id,
                $contribution_request
            );

            \DB::commit();
            return response()->json(array("result" => "success", "data" => $contribution_request));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function update(Request $request, \App\ContributionRequest $contribution_request)
    {
        $unauthorized = $this->is_not_authorized(['disabled_function']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'contribution_type' => 'required',
            'amount' => 'required'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $contribution_request->fill(
                $request->only(['amount', 'remarks', 'contribution_type'])
            );
            $contribution_request->save();
            \DB::commit();
            return response()->json(array("result" => "success", "data" => $contribution_request));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function list_selfservice(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\ContributionRequest::with([
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
            }
        ])
            ->where('employee_id', $this->me->employee_details->id);

        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_requests_for_approver(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['approve_contribution_request']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $query = \App\ContributionRequest::with([
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
        ])
            ->joinSub($approver_list_query, 'approvers', function ($join) {
                $join->on('contribution_request.approval_request_id', '=', 'approvers.approval_request_id');
            })
            ->leftJoin('employment_and_compensation', 'contribution_request.employee_id', 'employment_and_compensation.employee_id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('personal_information', 'contribution_request.employee_id', 'personal_information.employee_id')
            ->where('approvers.can_approve', '=', '1')
            ->select(
                'contribution_request.*',
                'approvers.*',
                \DB::raw('CONCAT(
                IFNULL(personal_information.last_name, \'\'),
                    \', \',
                    IFNULL(personal_information.first_name, \'\'),
                    \' \',
                    IFNULL(personal_information.middle_name, \'\')
            ) as name'),
                'departments.department_name'
            );

        // filtering
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        return response()->json($response);
    }

    public function approve_reject_request(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['approve_contribution_request']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'contribution_requests' => 'required|array',
            'contribution_requests.*' => 'exists:contribution_request,id',
            'status' => 'required|in:-1,1'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $result_contribution_requests = array();
            foreach ($request->input('contribution_requests') as $contribution_request_id) {
                $contribution_request = \App\ContributionRequest::find($contribution_request_id);
                $result = app('\App\Http\Controllers\AppFlowController')->approve_reject_request($contribution_request->approval_request_id, $this->me->employee_details->id, $request->input('status'));
                if ($result === 'request_approved' || $result === 'request_rejected') {
                    $contribution_request->status = $result === 'request_approved' ? 1 : -1;
                    $contribution_request->save();

                    $employee = \App\Employee::where('id', $contribution_request->employee_id)->first();
                    \App\Notification::create_user_notification(
                        $employee->users_id,
                        'Your request to adjust ' .
                        //$contribution_request->contribution_type
                        'Pag Ibig Contribution'
                        . ' is ' . ($result === 'request_approved' ? 'Approved' : 'Declined'),
                        \App\Notification::NOTIFICATION_SOURCE_CONTRIBUTION,
                        $contribution_request->id,
                        $contribution_request
                    );

                }
                array_push($result_contribution_requests, array(
                    'id' => $contribution_request->id,
                    'status' => $contribution_request->status,
                ));
            }
            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $result_contribution_requests));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => $e->getMessage()
            ), 400);
        }
    }
}
