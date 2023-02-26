<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class DocumentRequestController extends Controller
{
    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'document_request_type_id' => 'required|integer',
            'extra_id' => 'required_if:document_request_type_id,1|array'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $document_request_query = \DB::table('document_request')
            ->where([
                ['status', 0], ['employee_id', $this->me->employee_details->id],
                ['document_request_type_id', request('document_request_type_id')]
            ]);

        if ($request->filled('extra_id')) {
            $document_request_query->whereIn('extra_id', request('extra_id'));
        }
        if ($document_request_query->exists()) {
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => "Sorry! There is a pending request for one of the documents you are requesting."
            ), 400);
        }

        $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('employee_document', $this->me->employee_details->id);
        if ($app_flow_id === -1) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
        }

        $documents_requested = array();
        \DB::beginTransaction();
        try {
            if (request('document_request_type_id') !== 1) {
                $document = new \App\DocumentRequest();
                $document->employee_id = $this->me->employee_details->id;
                $document->document_request_type_id = request('document_request_type_id');
                $document->remarks = request('remarks');
                $document->status = 0;
                $document->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                    ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'employee_document');
                $document->save();

                $this->log_user_action(
                    Carbon::parse($document->created_at)->toDateString(),
                    Carbon::parse($document->created_at)->toTimeString(),
                    $this->me->id,
                    $this->me->name,
                    "Created a Document Request: " . $document->id,
                    "Self Service"
                );
                \App\Notification::create_hr_notification(
                    ['view_document_requests', 'approve_document_request'],
                    $this->me->name . ' requested for a document',
                    \App\Notification::NOTIFICATION_SOURCE_DOCUMENT,
                    $document->id,
                    $document
                );

                array_push($documents_requested, $document);
            } else {
                foreach (request('extra_id') as $extra_id) {
                    $document = new \App\DocumentRequest();
                    $document->employee_id = $this->me->employee_details->id;
                    $document->document_request_type_id = request('document_request_type_id');
                    $document->remarks = request('remarks');
                    $document->status = 0;
                    $document->extra_id = $extra_id;
                    $document->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                        ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'employee_document');
                    $document->save();

                    $this->log_user_action(
                        Carbon::parse($document->created_at)->toDateString(),
                        Carbon::parse($document->created_at)->toTimeString(),
                        $this->me->id,
                        $this->me->name,
                        "Created a Document Request: " . $document->id,
                        "Self Service"
                    );

                    \App\Notification::create_hr_notification(
                        ['view_document_requests', 'approve_document_request'],
                        $this->me->name . ' requested for a document',
                        \App\Notification::NOTIFICATION_SOURCE_DOCUMENT,
                        $document->id,
                        $document
                    );

                    array_push($documents_requested, $document);
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => "Sorry! There was a problem saving your requests."
            ), 400);
        }

        return response()->json(array("result" => "Request Successful", "data" => $documents_requested));
    }

    public function list(Request $request, $list_type, $user_id = null)
    {
        $query = \DB::table('document_request')
            ->leftJoin('documents', 'documents.id', 'document_request.document_id')
            ->leftJoin('employment_and_compensation', 'document_request.employee_id', 'employment_and_compensation.employee_id')
            ->select(
                'document_request.employee_id',
                'employment_and_compensation.department_id',
                'document_request.id',
                'documents.file_name',
                'document_request.status',
                'document_request.created_at',
                'document_request.updated_at',
                'document_request.remarks'
            );

        if ($list_type == "user_all") {
            $query->where([['document_request.employee_id', '=', $user_id]]);
        }
        if ($list_type == "user_history") {
            $query->where([['document_request.employee_id', '=', $user_id], ['status', '!=', 0]]);
        }
        if ($list_type == "hris_history") {
            $query->where([['status', '!=', 0]]);
        }


        // filtering
        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        $data = $response['data'];

        $result = array();
        foreach ($data as $document) {
            $item = array();
            $item['id'] = $document->id;
            if ($list_type == "hris_history" or $list_type == "hris_all") { //HRIS INFO ONLY
                $item['name'] = \DB::table('employees')
                    ->leftJoin('personal_information', 'employees.id', '=', 'personal_information.employee_id')
                    ->where('employees.id', '=', $document->employee_id)
                    ->select(['first_name', 'middle_name', 'last_name'])->first();
            }
            $item['nature_of_file'] = $document->file_name;
            $item['department'] = \DB::table('departments')
                ->where('id', '=', $document->department_id)
                ->select(array('id', 'department_name', 'code'))
                ->first();
            $item['date_request'] = Carbon::parse($document->created_at)->format('Y-m-d H:i:s');
            $item['date_approve'] = Carbon::parse($document->updated_at)->format('Y-m-d H:i:s');
            $item['remarks'] = $document->remarks;
            $item['status'] = $document->status;
            // if ($document->status != 0) {
            $item['approvers'] = app('\App\Http\Controllers\ApprovalFlowController')->list_approvers($document->id, 6);
            // }

            array_push($result, $item);
        }

        $response['data'] = $result;
        return response()->json($response);
    }

    //list pending, upcoming/approved or declined FOR USER
    public function list_user_doc_requests(Request $request, $user_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $logged_in_employee_id = \DB::table('employees')
            ->where('employees.users_id', '=', $this->me->id)
            ->first();

        $query = \DB::table('document_request')
            ->leftJoin('document_request_type', 'document_request.document_request_type_id', 'document_request_type.id')
            ->where([['document_request.employee_id', '=', $logged_in_employee_id->id]])
            ->select(
                'document_request.employee_id',
                'document_request.id',
                'document_request_type.name',
                'document_request.status',
                'document_request.created_at',
                'document_request.updated_at',
                'document_request.remarks'
            );

        // filtering
        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        // return response()->json($response);
        $data = $response['data'];

        $result = array();
        foreach ($data as $document) {
            $item = array();
            $item['id'] = $document->id;

            $item['nature_of_file'] = $document->name;
            // $item['department'] = \DB::table('departments')
            //                 ->where('id', '=', $document->department_id)
            //                 ->select(array('id', 'department_name', 'code'))
            //                 ->first();
            $item['date_request'] = Carbon::parse($document->created_at)->format('Y-m-d H:i:s');
            $item['date_approve'] = Carbon::parse($document->updated_at)->format('Y-m-d H:i:s');
            $item['remarks'] = $document->remarks;
            $item['status'] = $document->status;
            // if ($document->status != 0) {
            $item['approvers'] = app('\App\Http\Controllers\ApprovalFlowController')->list_approvers($document->id, 6);
            // }

            array_push($result, $item);
        }

        $response['data'] = $result;
        return response()->json($response);
    }

    //list history FOR USER
    public function list_user_doc_requests_history(Request $request, $user_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $logged_in_employee_id = \DB::table('employees')
            ->where('employees.users_id', '=', $this->me->id)
            ->first();

        return $this->list($request, "user_history", $logged_in_employee_id->id);
    }

    //list pending, upcoming/approved or declined FOR HRIS
    public function list_all_document_requests(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_document_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $logged_in_employee_id = \DB::table('employees')
            ->where('employees.users_id', '=', $this->me->id)
            ->first();

        $query = \DB::table('document_request')
            ->leftJoin('document_request_type', 'document_request.document_request_type_id', 'document_request_type.id')
            ->where([['document_request.employee_id', '=', $logged_in_employee_id->id]])
            ->select(
                'document_request.employee_id',
                'document_request.id',
                'document_request_type.name',
                'document_request.status',
                'document_request.created_at',
                'document_request.updated_at',
                'document_request.remarks'
            );

        // filtering
        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        // return response()->json($response);
        $data = $response['data'];

        $result = array();
        foreach ($data as $document) {
            $item = array();
            $item['id'] = $document->id;

            $item['nature_of_file'] = $document->name;
            // $item['department'] = \DB::table('departments')
            //                 ->where('id', '=', $document->department_id)
            //                 ->select(array('id', 'department_name', 'code'))
            //                 ->first();
            $item['date_request'] = Carbon::parse($document->created_at)->format('Y-m-d H:i:s');
            $item['date_approve'] = Carbon::parse($document->updated_at)->format('Y-m-d H:i:s');
            $item['remarks'] = $document->remarks;
            $item['status'] = $document->status;
            // if ($document->status != 0) {
            $item['approvers'] = app('\App\Http\Controllers\ApprovalFlowController')->list_approvers($document->id, 6);
            // }

            array_push($result, $item);
        }

        $response['data'] = $result;
        return response()->json($response);
    }

    //list history FOR HRIS
    public function list_all_document_requests_history(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_document_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        return $this->list($request, "hris_history");
    }

    public function set_status($data)
    {
        $unauthorized = $this->is_not_authorized(['approve_document_request']);
        if ($unauthorized) {
            return $unauthorized;
        }

        foreach ($data as $request) {
            $query = \DB::table('document_request')
                ->where('id', $request['request_id'])
                ->update(array(
                    "status" => $request['is_approved']
                ));
        }

        return response()->json(array("result" => "Updated Success", "data" => $data));
    }

    public function list_employee_document_requests(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\DocumentRequest::with([
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
            'attachment'  => function ($q) {
                $q->select('id', 'file_name');
            }
        ])
            ->select(
                'document_request.id',
                'extra_id',
                'approval_request_id',
                'status',
                'name as nature_of_file',
                'document_request.created_at',
                'document_request.updated_at',
                'remarks'
            )
            ->leftJoin('document_request_type', 'document_request.document_request_type_id', 'document_request_type.id')
            ->where('document_request.employee_id', $this->me->employee_details->id);

        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_requests_for_approver(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_document_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $query = \App\DocumentRequest::with([
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
            'attachment'  => function ($q) {
                $q->select('id', 'file_name');
            }
        ])
            ->joinSub($approver_list_query, 'approvers', function ($join) {
                $join->on('document_request.approval_request_id', '=', 'approvers.approval_request_id');
            })
            ->leftJoin('document_request_type', 'document_request.document_request_type_id', '=', 'document_request_type.id')
            ->leftJoin('employment_and_compensation', 'document_request.employee_id', 'employment_and_compensation.employee_id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('personal_information', 'document_request.employee_id', 'personal_information.employee_id')
            ->where('approvers.can_approve', '=', '1')
            ->select(
                'document_request.*',
                'approvers.*',
                'document_request_type.name as nature_of_file',
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
        $unauthorized = $this->is_not_authorized(['approve_document_request']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'document_requests' => 'required|array',
            'document_requests.*' => 'exists:document_request,id',
            'status' => 'required|in:-1,1'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $result_document_requests = array();
            foreach ($request->input('document_requests') as $document_request_id) {
                $document_request = \App\DocumentRequest::find($document_request_id);
                $result = app('\App\Http\Controllers\AppFlowController')->approve_reject_request($document_request->approval_request_id, $this->me->employee_details->id, $request->input('status'));
                if ($result === 'request_approved' || $result === 'request_rejected') {
                    $document_request->status = $result === 'request_approved' ? 1 : -1;
                    $document_request->save();

                    $employee = \App\Employee::where('id', $document_request->employee_id)->first();
                    \App\Notification::create_user_notification(
                        $employee->users_id,
                        'Your request for a document is ' . ($result === 'request_approved' ? 'Approved' : 'Declined'),
                        \App\Notification::NOTIFICATION_SOURCE_DOCUMENT,
                        $document_request->id,
                        $document_request
                    );

                }
                array_push($result_document_requests, array(
                    'id' => $document_request->id,
                    'status' => $document_request->status,
                ));
            }
            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $result_document_requests));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => $e->getMessage()
            ), 400);
        }
    }
}
