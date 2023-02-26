<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class LoanRequestController extends Controller
{
    public function create_or_update(Request $request, \App\LoanRequest $loanRequest, $is_new = false)
    {
        $validator_arr = [
            'loan_type_id' => 'required|numeric',
            'ammortization_number' => 'required',
            'ammortization_period' => 'required',
            'purpose' => 'required',
            'loan_amount' => 'required|numeric'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $pending_count = \App\LoanRequest::where([
            ['status', '0'],
            ['loan_type_id', $request['loan_type_id']],
            ['employee_id', $this->me->employee_details->id]
        ])->count();

        $ongoing_count = \App\LoanRequest::where([
            ['status', '1'],
            ['loan_type_id', request('loan_type_id')],
            ['employee_id', $this->me->employee_details->id]
        ])
            ->whereRaw('
            loan_requests.loan_amount > (
                SELECT IFNULL(SUM(loan_payments.amount), 0)
                FROM loan_payments
                WHERE loan_payments.loan_request_id = loan_requests.id
            )
        ')->count();

        if ($pending_count > 0 || $ongoing_count > 0) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'There is an active loan request for this type'], 400);
        }

        $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('employee_loan', $this->me->employee_details->id);
        if ($app_flow_id === -1) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
        }

        $loanRequest->employee_id = $this->me->employee_details->id;
        $loanRequest->loan_type_id = request('loan_type_id');
        $loanRequest->loan_amount = round(request('loan_amount'), 2);
        $loanRequest->ammortization_number = request('ammortization_number');
        $loanRequest->ammortization_period = request('ammortization_period');
        $loanRequest->purpose = request('purpose');
        if ($request->has('attachments')) {
            $loanRequest->attachments = request('attachments');
        }

        \DB::beginTransaction();
        try {
            if ($is_new) {
                $loanRequest->status = 0;
                $loanRequest->created_by = $this->me->id;
                $loanRequest->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                    ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'employee_loan');
                $loanRequest->save();

                $this->log_user_action(
                    Carbon::parse($loanRequest->created_at)->toDateString(),
                    Carbon::parse($loanRequest->created_at)->toTimeString(),
                    $this->me->id,
                    $this->me->name,
                    "Created a Loan Request",
                    "Self Service"
                );
                \App\Notification::create_hr_notification(
                    ['view_loan_requests', 'approve_loan_requests'],
                    $this->me->name . ' requested for ' . $loanRequest->loan_type->loan_name,
                    \App\Notification::NOTIFICATION_SOURCE_LOAN,
                    $loanRequest->id,
                    $loanRequest
                );

            } else {
                $loanRequest->updated_by = $this->me->id;
                $loanRequest->save();
            }
            \DB::commit();
            return response()->json($loanRequest);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, new \App\LoanRequest(), true);
    }

    public function update(Request $request, \App\LoanRequest $loanRequest)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, $loanRequest);
    }

    public function view_outstanding_balance(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $result = \App\LoanRequest::with('department')
            ->where([
                ['loan_requests.status', '=', 1],
                ['loan_requests.employee_id', '=', $this->me->employee_details->id]
            ])
            ->get();

        return response()->json($result);
    }

    public function employee_outstanding_balance(Request $request, \App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $result = \App\LoanRequest::where([
            ['loan_requests.status', '=', 1],
            ['loan_requests.employee_id', '=', $employee->id]
        ])
            ->get()->map->only([
                'loan_name',
                'updated_at',
                'remaining_balance'
            ]);
        $result = $result->filter(function ($entry) {
            return $entry['remaining_balance'] > 0;
        })->toArray();

        return response()->json(array_values($result));
    }

    public function employee_outstanding_balance_self(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $result = \App\LoanRequest::where([
            ['loan_requests.status', '=', 1],
            ['loan_requests.employee_id', '=', $this->me->employee_details->id]
        ])
            ->get()->map->only([
                'loan_name',
                'updated_at',
                'remaining_balance'
            ]);

        return response()->json($result);
    }

    public function upload_loan_file(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $request = $request->all();

        foreach ($request as $item) {
            $new = new \App\LoanDocuments();
            $new->user_id = $this->me->id;
            $new->loan_id_request = $item['loan_id_request'];
            $new->file_name = $item['file_name'];
            $new->file_date = $item['file_date'];
            $new->file_remarks = $item['file_remarks'];
            $new->file_type = $item['file_type'];
            $new->file_location = $item['file_location'];
            $new->status = true;
            $new->save();
        }

        return response()->json($new);
    }

    public function delete_files_loan($file_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\LoanDocuments::where('id', $file_id)->update(array('status' => 0));
        return "deleted";
    }

    public function read_files_loan()
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\LoanDocuments::where('user_id', $this->me->id)->where('status', 1)->get();
        return response()->json($query);
    }

    public function loan_active_list()
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $result = \App\LoanRequest::with('department')
            ->where([
                ['status', '=', 1],
                ['employee_id', '=', $this->me->employee_details->id]
            ])
            ->get();

        return response()->json($result);
    }

    public function read_files_loan_with_params($emp_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $user_id = \DB::table('employees')->where('id', $emp_id)->first();
        if ($user_id) {
            $id = $user_id->users_id;
        } else {
            $id = 0;
        }

        $query = \App\LoanDocuments::where('user_id', $id)->where('status', 1)->get();
        return response()->json($query);
    }

    public function list_summary(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $declined_count = \App\LoanRequest::where('status', '-1')->count();
        $completed_count = \App\LoanRequest::where('status', '1')
            ->whereRaw('
            loan_requests.loan_amount <= (
                SELECT IFNULL(SUM(loan_payments.amount), 0)
                FROM loan_payments
                WHERE loan_payments.loan_request_id = loan_requests.id
            )
        ')->count();
        $ongoing_count = \App\LoanRequest::where('status', '1')
            ->whereRaw('
            loan_requests.loan_amount > (
                SELECT IFNULL(SUM(loan_payments.amount), 0)
                FROM loan_payments
                WHERE loan_payments.loan_request_id = loan_requests.id
            )
        ')->count();
        $due_count = 0; // TODO

        $finalize = [
            (object) [
                "title" => "On-going",
                "total" => $ongoing_count
            ],
            (object) [
                "title" => "Completed",
                "total" => $completed_count
            ],
            (object) [
                "title" => "Due",
                "total" => $due_count
            ],
            (object) [
                "title" => "Declined",
                "total" => $declined_count
            ]
        ];

        return response()->json(array("result" => "success", "data" => $finalize));
    }

    public function list_history(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\LoanRequest::where('status', '!=', 0);

        // filtering
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_employee_history(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\LoanRequest::with(['approvers.approver', 'department'])
            ->where([
                ['status', '!=', 0],
                ['employee_id', '=', $this->me->employee_details->id]
            ]);

        // filtering
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        $response = (object) $response;
        foreach ($response->data as $loan_entry) {
            if ($loan_entry->status === 1 && $loan_entry->remaining_balance === 0) {
                $loan_entry->status = 2;
            }
        }

        return response()->json($response);
    }

    public function list_employee_pending(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\LoanRequest::with(['approvers.approver', 'department'])
            ->where([
                ['status', '=', 0],
                ['employee_id', '=', $this->me->employee_details->id]
            ]);

        // filtering
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function read(Request $request, $loan_request_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $loan_request = \App\LoanRequest::with([
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
            'requestor.employee_number',
        ])
            ->findOrFail($loan_request_id);
        $loan_request['amount_paid'] = $loan_request->getAmountPaidAttribute();
        $loan_request['remaining_balance'] = $loan_request->getRemainingBalanceAttribute();

        unset($loan_request['employment_details']);
        unset($loan_request['loan_type']);

        return response()->json(array("result" => "success", "data" => $loan_request), 200);
    }

    public function list_requests_for_approver(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_loan_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $query = \App\LoanRequest::with([
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
            ->joinSub($approver_list_query, 'approver', function ($join) {
                $join->on('loan_requests.approval_request_id', '=', 'approver.approval_request_id');
            })
            ->leftJoin('loans', 'loan_requests.loan_type_id', '=', 'loans.id')
            ->leftJoin('employment_and_compensation', 'loan_requests.employee_id', 'employment_and_compensation.employee_id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('personal_information', 'loan_requests.employee_id', 'personal_information.employee_id')
            ->leftJoin('personal_information as direct_report_info', 'employment_and_compensation.direct_report_id', 'direct_report_info.employee_id')
            ->where([
                ['approver.can_approve', '=', '1'],
                ['loan_requests.status', '=', '0'],
            ])
            ->select(
                'loan_requests.*',
                'loans.loan_name',
                'approver.can_approve',
                'personal_information.mobile_number',
                'personal_information.email_address',
                \DB::raw('CONCAT(
                IFNULL(personal_information.last_name, \'\'),
                    \', \',
                    IFNULL(personal_information.first_name, \'\'),
                    \' \',
                    IFNULL(personal_information.middle_name, \'\')
            ) as name'),
                \DB::raw('CONCAT(
                IFNULL(direct_report_info.last_name, \'\'),
                    \', \',
                    IFNULL(direct_report_info.first_name, \'\'),
                    \' \',
                    IFNULL(direct_report_info.middle_name, \'\')
            ) as direct_report'),
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
        $unauthorized = $this->is_not_authorized(['approve_loan_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'loan_requests' => 'required|array',
            'loan_requests.*' => 'exists:loan_requests,id',
            'status' => 'required|in:-1,1'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $result_loan_requests = array();
            foreach ($request->input('loan_requests') as $loan_request_id) {
                $loan_request = \App\LoanRequest::find($loan_request_id);
                $result = app('\App\Http\Controllers\AppFlowController')->approve_reject_request(
                    $loan_request->approval_request_id,
                    $this->me->employee_details->id,
                    $request->input('status')
                );
                if ($result === 'request_approved' || $result === 'request_rejected') {
                    $loan_request->status = $result === 'request_approved' ? 1 : -1;
                    $loan_request->save();
                    $employee = \App\Employee::where('id', $loan_request->employee_id)->first();
                    \App\Notification::create_user_notification(
                        $employee->users_id,
                        'Your request for ' . $loan_request->loan_type->loan_name . ' is ' . ($result === 'request_approved' ? 'Approved' : 'Declined'),
                        \App\Notification::NOTIFICATION_SOURCE_LOAN,
                        $loan_request->id,
                        $loan_request
                    );

                }
                array_push($result_loan_requests, array(
                    'id' => $loan_request->id,
                    'status' => $loan_request->status,
                ));
            }
            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $result_loan_requests));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => $e->getMessage()
            ), 400);
        }
    }
}
