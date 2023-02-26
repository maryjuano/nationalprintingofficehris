<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\OTRequest;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class OTController extends Controller
{
    public function create_or_update(Request $request, \App\OTRequest $ot_request, $is_new = false)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('employee_ot', $this->me->employee_details->id);
        if ($app_flow_id === -1) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
        }

        $request_all = $request->all();

        foreach ($request_all as $items) {
            $ot_request->employee_id = $employee_id;
            $ot_request->start = $items['start_date'];
            $ot_request->end = $items['end_date'];
            $ot_request->requested_by = $items['requested_by'];
            $ot_request->schedule = $items['schedule'];
            $ot_request->is_requested = $items['is_requested'];

            if (isset($items['remarks'])) {
                $ot_request->remarks = $items['remarks'];
            }
            $ot_request->status = 0;

            \DB::beginTransaction();
            try {
                if ($is_new) {
                    $ot_request->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                        ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'employee_ot');

                    $ot_request->save();
                } else {
                    $ot_request->save();
                }
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }
            \DB::commit();
        }

        return $this->read($ot_request->id);
    }

    public function read($ot_request_id)
    {
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!($this->me->claims['temporary'] ?? $this->DISABLE_AUTH)) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }

        $ot_request = \App\OTRequest::with([
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
        ])->findOrFail($ot_request_id);

        return response()->json(array("result" => "success", "data" => $ot_request));
    }

    public function create_authority_to_ot(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        return response()->json(['error' => 'validation_failed', 'messages' => $request->all()], 400);
    }

    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, new \App\OTRequest(), true);
    }

    public function update(Request $request, \App\OTRequest $ot_request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, $ot_request);
    }

    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\OTRequest::with([
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
            ->leftJoin('personal_information', 'ot_request.employee_id', 'personal_information.employee_id')
            ->select(
                'ot_request.*',
                \DB::raw('CONCAT(
                IFNULL(personal_information.last_name, \'\'),
                    \', \',
                    IFNULL(personal_information.first_name, \'\'),
                    \' \',
                    IFNULL(personal_information.middle_name, \'\')
            ) as name'),
            );

        if (request('employee_id')) {
            $query = $query->where('employee_id', request('employee_id'));
        }

        // filtering
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_authority_ot(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\OTRequest::with('approvers')
            ->where('is_requested', 1)
            ->leftJoin('personal_information', 'ot_request.employee_id', 'personal_information.employee_id')
            ->select(
                'ot_request.*',
                \DB::raw('CONCAT(
                IFNULL(personal_information.last_name, \'\'),
                    \', \',
                    IFNULL(personal_information.first_name, \'\'),
                    \' \',
                    IFNULL(personal_information.middle_name, \'\')
            ) as name'),
            );

        // filtering
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_employee_ot_history(Request $request)
    {
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!($this->me->claims['temporary'] ?? $this->DISABLE_AUTH)) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }

        $query = \App\OTRequest::where([
            ['ot_request.employee_id', $this->me->employee_details->id],
            ['ot_request.status', '!=', '0']
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
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!($this->me->claims['temporary'] ?? $this->DISABLE_AUTH)) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }

        $query = \App\OTRequest::with([
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
            ->where('ot_request.employee_id', !$employee_id ? $this->me->employee_details->id : $employee_id);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start', [$request->input('start_date'), $request->input('end_date')]);
            $query->whereBetween('end', [$request->input('start_date'), $request->input('end_date')]);
        }

        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_requests_for_approver(Request $request)
    {
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!($this->me->claims['temporary'] ?? $this->DISABLE_AUTH)) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $query = \App\OTRequest::with([
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
                $join->on('ot_request.approval_request_id', '=', 'approvers.approval_request_id');
            })
            ->leftJoin('employment_and_compensation', 'ot_request.employee_id', 'employment_and_compensation.employee_id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('personal_information', 'ot_request.employee_id', 'personal_information.employee_id')
            ->where('approvers.can_approve', '=', '1')
            ->select(
                'ot_request.*',
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
            $query->whereBetween('start', [$request->input('start_date'), $request->input('end_date')]);
            $query->whereBetween('end', [$request->input('start_date'), $request->input('end_date')]);
        }

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
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'ot_requests' => 'required|array',
            'ot_request.*' => 'exists:ot_request,id',
            'status' => 'required|in:-1,1'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $result_ot_requests = array();
            foreach ($request->input('ot_requests') as $ot_request_id) {
                $ot_request = \App\OTRequest::find($ot_request_id);
                $result = app('\App\Http\Controllers\AppFlowController')->approve_reject_request($ot_request->approval_request_id, $this->me->employee_details->id, $request->input('status'));
                if ($result === 'request_approved' || $result === 'request_rejected') {
                    $ot_request->status = $result === 'request_approved' ? 1 : -1;
                    $ot_request->save();
                }
                array_push($result_ot_requests, array(
                    'id' => $ot_request->id,
                    'status' => $ot_request->status,
                ));
            }
            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $result_ot_requests));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => $e->getMessage()
            ), 400);
        }
    }
}
