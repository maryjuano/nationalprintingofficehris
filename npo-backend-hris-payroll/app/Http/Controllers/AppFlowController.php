<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AppFlowController extends Controller
{
    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['create_approval_flow']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'name' => 'required|unique:app_flows',
            'section_id' => 'required',
            'department_id' => 'required',
            'request_type' => 'required',
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $app_flow_exists = false;
        if (!request('pick_employee')) {
            $app_flow_exists = \DB::table('app_flows')
                ->where([
                    ['request_type', request('request_type')],
                    ['department_id', request('department_id')],
                    ['section_id', request('section_id')],
                    ['pick_employee', false]
                ])
                ->exists();
        } else {
            $app_flow_exists = \DB::table('app_flows')
                ->where([
                    'request_type' => request('request_type'),
                    'department_id' => request('department_id'),
                    'section_id' => request('section_id')
                ])
                ->rightJoin('app_flow_employee', 'app_flows.id', '=', 'app_flow_employee.app_flow_id')
                ->whereIn('app_flow_employee.requestor_id', request('employee_id', []))
                ->exists();
        }

        if ($app_flow_exists) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'Approval flow for this request and employee group already exists.'], 400);
        }

        $approval_flow = \App\AppFlow::firstOrNew(['name' => $request->input('name')]);
        $approval_flow->fill(
            $request->only(['request_type', 'department_id', 'section_id', 'pick_employee'])
        );

        $approval_flow->status = request('status', true);

        \DB::beginTransaction();
        try {
            $approval_flow->created_by = $this->me->id;
            $approval_flow->updated_by = $this->me->id;
            $approval_flow->save();

            if ($approval_flow->id) {
                $this->assign_employees_to_appflow($request, $approval_flow->id, false);
                $this->create_appflow_levels($request, $approval_flow->id);
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'validation_failed', 'messages' => $e->getMessage()], 400);
        }

        return response()->json($approval_flow);
    }

    public function update(Request $request, \App\AppFlow $approval_flow)
    {
        $unauthorized = $this->is_not_authorized(['edit_approval_flow']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $active_approval_request_exists = \App\ApprovalRequest::where([
            ['status', 0],
            ['app_flow_id', $approval_flow->id]
        ])->exists();

        if ($active_approval_request_exists) {
            return response()->json(['error' => 'update declined.', 'messages' => 'There is a pending request using this approval flow'], 400);
        }

        $approval_flow->fill(
            array_filter($request->only(['name', 'request_type', 'department_id', 'section_id', 'pick_employee']), 'strlen')
        );
        $approval_flow->updated_by = $this->me->id;
        $approval_flow->save();

        if ($approval_flow->id) {
            $this->assign_employees_to_appflow($request, $approval_flow->id, true);
            $this->create_appflow_levels($request, $approval_flow->id);
        }

        return response()->json($approval_flow);
    }

    public function set_status(Request $request, \App\AppFlow $approval_flow)
    {
        $unauthorized = $this->is_not_authorized(['toggle_approval_flow']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $pending_approval_requests = \DB::table('approval_requests')
            ->where('app_flow_id', $approval_flow->id)
            ->where('status', 0)
            ->count();

        if ($pending_approval_requests > 0) {

            // return response($content = , $status = 400)
            // ->json();
            return response()->json(
                [
                    "message" => "There are still pending requests for this approval flow."
                ],
                400
            );
        } else {
            $approval_flow->status = $request['status'];
            $approval_flow->save();
            return response()->json(array("data" => $approval_flow, "result" => "success"));
        }
    }

    private function assign_employees_to_appflow($request, $approval_flow_id, $is_update)
    {
        if (!$request->pick_employee) {
            $employees = \App\EmploymentAndCompensation::where('section_id', $request->section_id)->pluck('employee_id');
        } else {
            $employees = $request->employee_id;

            // Clean up deleted employees in approval flow.
            \DB::table('app_flow_employee')
                ->whereNotIn('requestor_id', $employees)
                ->where('app_flow_id', $approval_flow_id)
                ->delete();
        }

        foreach ($employees as $employee) {
            $dataExists = \DB::table('app_flow_employee')
                ->join('app_flows', 'app_flows.id', '=', 'app_flow_employee.app_flow_id')
                ->where([
                    ['app_flows.request_type', $request->request_type],
                    ['app_flow_employee.requestor_id', $employee]
                ])
                ->exists();

            $dataProtected = \DB::table('app_flow_employee')
                ->join('app_flows', 'app_flows.id', '=', 'app_flow_employee.app_flow_id')
                ->where([
                    ['app_flows.request_type', $request->request_type],
                    ['app_flow_employee.requestor_id', $employee],
                    ['app_flows.pick_employee', true]
                ])
                ->exists();

            if ($dataExists && $dataProtected && !$is_update) {
                throw new \Exception('Employee already has an approval flow for this request type.');
            }

            if (!$dataExists) {
                \DB::table('app_flow_employee')->insert([
                    [
                        'app_flow_id' => $approval_flow_id,
                        'requestor_id' => $employee,
                        'created_at' => Carbon::now()->toDateString(),
                        'updated_at' => Carbon::now()->toDateString(),
                    ]
                ]);
            }
        }
    }

    private function create_appflow_levels($request, $approval_flow_id)
    {
        $approval_levels = $request->approval_levels;
        $parent_id = -1;
        $currentAppFlowLevels = \App\AppFlowLevel::where('app_flow_id', $approval_flow_id)->select('id')->get();
        \App\AppFlowLevel::where('app_flow_id', $approval_flow_id)->delete();
        \App\AppFlowLevelEmployee::whereIn('app_flow_levels_id', $currentAppFlowLevels)->delete();
        foreach ($approval_levels as $approval_level) {
            if (count($approval_level['approvers']) === 0) {
                continue;
            }
            $created_app_flow_level = \App\AppFlowLevel::create(
                [
                    'created_by' => $this->me->id,
                    'updated_by' => $this->me->id,
                    'selection_mode' => $approval_level['selection_mode'],
                    'description' => $approval_level['description'],
                    'dependent_on' => $parent_id,
                    'app_flow_id' => $approval_flow_id
                ]
            );
            $parent_id = $created_app_flow_level->id;
            foreach ($approval_level['approvers'] as $approver) {
                \App\AppFlowLevelEmployee::create(
                    [
                        'app_flow_levels_id' => $created_app_flow_level->id,
                        'approver_id' => $approver['approver_id'],
                        'can_approve' => isset($approver['can_approve']) ? $approver['can_approve'] : false
                    ]
                );
                // $app_flow_level_employee = \App\AppFlowLevelEmployee::where([
                //     ['app_flow_levels_id', '=', $created_app_flow_level->id],
                //     ['approver_id', '=', $approver['approver_id']]
                // ])->first();
                // if ($app_flow_level_employee !== null) {
                //     if (isset($approver['deleted']) && $approver['deleted']) {
                //         $app_flow_level_employee->delete();
                //     } else {
                //         $app_flow_level_employee->can_approve = isset($approver['can_approve']) ? $approver['can_approve'] : false;
                //         $app_flow_level_employee->save();
                //     }
                // } else {
                //     if (isset($approver['deleted']) && $approver['deleted']) {
                //     } else {
                //         \App\AppFlowLevelEmployee::create(
                //             [
                //                 'app_flow_levels_id' => $created_app_flow_level->id,
                //                 'approver_id' => $approver['approver_id'],
                //                 'can_approve' => isset($approver['can_approve']) ? $approver['can_approve'] : false
                //             ]
                //         );
                //     }
                // }
            }
        }
    }

    public function create_approval_flow_for_request($created_by, $requestor_id, $app_flow_id, $request_type)
    {
        $approval_request = \App\ApprovalRequest::create(array(
            'requestor_id' => $requestor_id,
            'request_type' => $request_type,
            'created_by' => $created_by,
            'app_flow_id' => $app_flow_id
        ));

        $app_flow_levels = \DB::table('app_flow_employee')
            ->join('app_flows', 'app_flow_employee.app_flow_id', 'app_flows.id')
            ->join('app_flow_levels', 'app_flows.id', 'app_flow_levels.app_flow_id')
            ->where([
                ['app_flow_employee.requestor_id', $requestor_id],
                ['app_flows.request_type', $request_type]
            ])
            ->select('app_flow_levels.*')
            ->orderBy('app_flow_levels.dependent_on')
            ->get();

        $parent_id = -1;
        foreach ($app_flow_levels as $app_flow_level) {
            $saved_approval_level = \App\ApprovalLevel::create(array(
                'approval_request_id' => $approval_request->id,
                'dependent_on' => $parent_id,
                'created_by' => $created_by,
                'description' => $app_flow_level->description
            ));
            $parent_id = $saved_approval_level->id;

            $approvers = \DB::table('app_flow_level_employee')
                ->where('app_flow_levels_id', $app_flow_level->id)
                ->get();

            if ($app_flow_level->selection_mode === 'one') {
                $approval_item = \App\ApprovalItem::create(array(
                    'approval_level_id' => $saved_approval_level->id,
                    'created_by' => $created_by,
                ));

                foreach ($approvers as $approver) {
                    \App\ApprovalItemEmployee::create(array(
                        'approval_item_id' => $approval_item->id,
                        'approver_id' => $approver->approver_id,
                        'can_approve' => $approver->can_approve
                    ));
                }
            } else {
                foreach ($approvers as $approver) {
                    $approval_item = \App\ApprovalItem::create(array(
                        'approval_level_id' => $saved_approval_level->id,
                        'created_by' => $created_by,
                    ));
                    $approval_item->status = $approver->can_approve ? 0 : 1;
                    $approval_item->save();

                    \App\ApprovalItemEmployee::create(array(
                        'approval_item_id' => $approval_item->id,
                        'approver_id' => $approver->approver_id,
                        'can_approve' => $approver->can_approve
                    ));
                }
            }
        }

        return $approval_request->id;
    }

    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_approval_flows']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \DB::table('app_flows')
            ->select(
                'app_flows.*',
                'departments.department_name as department_name',
                \DB::raw('COUNT(
                    app_flow_levels.app_flow_id
                ) as total_levels')
            )
            ->leftJoin('departments', 'departments.id', '=', 'app_flows.department_id')
            ->leftJoin('app_flow_levels', 'app_flow_levels.app_flow_id', '=', 'app_flows.id')
            ->groupBy('app_flow_levels.app_flow_id');
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        return response()->json($result);
    }

    private function list_approver_requests($request, $approver_id)
    {
        $query = \DB::table('approval_item_employee')
            ->where([
                ['approval_item_employee.approver_id', $approver_id],
                ['request_type', $request->request_type]
            ])
            ->leftJoin('approval_items', 'approval_item_employee.approval_item_id', '=', 'approval_items.id')
            ->leftJoin('approval_level', 'approval_items.approval_level_id', '=', 'approval_level.id')
            ->leftJoin('approval_requests', 'approval_level.approval_request_id', '=', 'approval_requests.id')
            ->select(
                'approval_requests.requestor_id',
                'approval_requests.request_type',
                'approval_item.status',
                '(
                    CASE
                        WHEN NOT EXISTS (
                            SELECT *
                            FROM approval_level as al
                            WHERE id = \'approval_level.dependent_on\'
                            AND status != 1
                        )
                        THEN CAST(1 AS BIT)
                        ELSE CAST(0 AS BIT)
                    END
                ) as can_approve'
            )
            ->get();
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        return response()->json($result);
    }

    private function list_requestor_requests($request, $requestor_id)
    {
        $query = \DB::table('approval_requests')
            ->where('requestor_id', $requestor_id);
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $result = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        return response()->json($result);
    }

    public function approve_reject_request($approval_request_id, $approver_id, $approval)
    {
        if ($approval !== -1 && $approval !== 1) {
            throw new \Exception('Invalid approval status: ' . $approval);
        }

        $for_approval = \DB::table('approval_item_employee')
            ->where([
                ['approval_item_employee.approver_id', $approver_id],
                ['approval_requests.id', $approval_request_id]
            ])
            ->join('approval_items', 'approval_items.id', '=', 'approval_item_employee.approval_item_id')
            ->join('approval_level', 'approval_level.id', '=', 'approval_items.approval_level_id')
            ->join('approval_requests', 'approval_requests.id', '=', 'approval_level.approval_request_id')
            ->select(
                'approval_requests.status as request_status',
                'approval_level.status as level_status',
                'approval_items.status as item_status',
                'approval_items.id as item_id',
                'approval_level.dependent_on as dependent_on',
                'approval_level.id as level_id',
                'approval_requests.id as request_id',
                'approval_item_employee.can_approve'
            )
            ->first();


        if ($for_approval === null) {
            throw new \Exception('No pending approval item for this approver.');
        }

        if ($for_approval->can_approve !== 1) {
            throw new \Exception('Unauthorized: approver cannot approve this request.');
        }
        //Commented out should not throw error when declined - Sept 30, 2020
        // if ($for_approval->request_status === -1) {
        //     throw new \Exception('Request has already been denied.');
        // }
        if ($for_approval->request_status === 1) {
            throw new \Exception('Request has already been approved.');
        }
        // if ($for_approval->level_status === -1) {
        //     throw new \Exception('Level has already been denied.');
        // }
        if ($for_approval->level_status === 1) {
            throw new \Exception('Level has already been approved.');
        }
        // if ($for_approval->item_status === -1) {
        //     throw new \Exception('Item has already been denied.');
        // }
        if ($for_approval->item_status === 1) {
            throw new \Exception('Item has already been approved.');
        }

        $dependency = \DB::table('approval_level')->where('id', $for_approval->dependent_on)->first();
        if ($dependency === null || $dependency->status === 1) {
            $approval_item = \App\ApprovalItem::find($for_approval->item_id);
            $approval_item->status = $approval;
            $approval_item->updated_by = $approver_id;
            $approval_item->save();

            if ($approval === -1) {
                $approval_level = \App\ApprovalLevel::find($for_approval->level_id);
                $approval_level->status = $approval;
                $approval_level->save();

                $approval_request = \App\ApprovalRequest::find($for_approval->request_id);
                $approval_request->status = $approval;
                $approval_request->save();

                return 'request_rejected';
            }

            $check_level = \DB::table('approval_items')
                ->where('approval_items.approval_level_id', $for_approval->level_id);

            if ($check_level->sum('status') == $check_level->count()) {
                $approval_level = \App\ApprovalLevel::find($for_approval->level_id);
                $approval_level->status = 1;
                $approval_level->save();

                $check_request = \DB::table('approval_level')
                    ->where('approval_level.approval_request_id', $for_approval->request_id);

                if ($check_request->sum('status') == $check_request->count()) {
                    $approval_request = \App\ApprovalRequest::find($for_approval->request_id);
                    $approval_request->status = 1;
                    $approval_request->save();

                    return 'request_approved';
                }

                return 'level_approved';
            }

            return 'item_approved';
        } else {
            throw new \Exception('Approver cannot approve one or more of the requests yet.');
        }
    }

    public function get_approver_requests_query($approver_id)
    {
        return \DB::table('approval_item_employee')
            ->where([
                ['approval_item_employee.approver_id', $approver_id],
            ])
            ->leftJoin('approval_items', 'approval_item_employee.approval_item_id', '=', 'approval_items.id')
            ->leftJoin('approval_level', 'approval_items.approval_level_id', '=', 'approval_level.id')
            ->leftJoin('approval_requests', 'approval_level.approval_request_id', '=', 'approval_requests.id')
            ->select(
                'approval_requests.id as approval_request_id',
                'approval_requests.request_type as approval_request_type',
                'approval_items.status as approval_request_status',
                \DB::raw('(
                    CASE
                        WHEN NOT EXISTS (
                            SELECT *
                            FROM approval_level as al
                            WHERE al.id = approval_level.dependent_on
                            AND status != 1
                        )
                        THEN 1
                        ELSE 0
                    END
                ) as can_approve'),
                \DB::raw('(
                    CASE
                        WHEN approval_requests.created_at != approval_requests.updated_at
                        THEN approval_requests.updated_at
                        ELSE NULL
                    END
                ) as approval_date')
            );
    }

    public function read(Request $request, \App\AppFlow $approval_flow)
    {
        $unauthorized = $this->is_not_authorized(['view_approval_flows']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $approval_flow = $this->enrich($approval_flow);

        return response()->json(array("result" => "success", "data" => $approval_flow), 200);
    }

    private function enrich($approval_flow)
    {
        $approval_flow['department_name'] = $approval_flow->department->department_name;
        $approval_levels = array();
        foreach ($approval_flow->levels as $approval_level) {
            $level = (object) array();
            $level->id = $approval_level->id;
            $level->description = $approval_level->description;
            $level->selection_mode = $approval_level->selection_mode;
            $level_approvers = array();
            foreach ($approval_level->approvers as $level_approver) {
                $approver = (object) array();
                $approver->id = $level_approver->pivot->id;
                $approver->approver_id = $level_approver->id;
                $approver->department_id = $level_approver->employment_and_compensation->department_id;
                $approver->can_approve = $level_approver->pivot->can_approve;
                array_push($level_approvers, $approver);
            }
            $level->approvers = $level_approvers;
            array_push($approval_levels, $level);
        }
        $approval_flow['approval_levels'] = $approval_levels;
        if ($approval_flow->pick_employee) {
            $approval_flow['employee_id'] = $approval_flow->requestors->pluck('id')->toArray();
        }
        return $approval_flow;
    }

    public function app_flow_id($request_type, $requestor_id)
    {
        $app_flow = \App\AppFlow::join('app_flow_employee', 'app_flows.id', '=', 'app_flow_employee.app_flow_id')
            ->where([
                'app_flows.request_type' => $request_type,
                'app_flow_employee.requestor_id' => $requestor_id,
                'app_flows.status' => 1
            ])
            ->select(
                'app_flows.id'
            )->first();
        if ($app_flow === null) {
            return -1;
        } else {
            return $app_flow->id;
        }
    }

    public function approval_flow_exists($request_type, $requestor_id)
    {
        return \DB::table('app_flows')
            ->where([
                'request_type' => $request_type,
                'app_flow_employee.requestor_id' => $requestor_id
            ])
            ->join('app_flow_employee', 'app_flows.id', '=', 'app_flow_employee.app_flow_id')
            ->exists();
    }

    public function add_edit_remarks(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'approver_id' => 'required',
            'approval_item_employee_id' => 'exists:approval_item_employee,id',
            'remarks' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $approval_item_employee = \App\ApprovalItemEmployee::where([
                ['approver_id', $request->input('approver_id')],
                ['id', $request->input('approval_item_employee_id')],
            ])->first();

            if (!$approval_item_employee) {
                throw new \Exception('Approver does not have permission to add a remark.');
            }

            $approval_item_employee->remarks = $request->input('remarks');
            $approval_item_employee->save();
            \DB::commit();
            return response()->json(['result' => 'success', 'data' => $approval_item_employee], 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'validation_failed', 'messages' => $e->getMessage()], 400);
        }
    }

    public function add_edit_attachments(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'approver_id' => 'exists:approval_item_employee,approver_id',
            'approval_item_employee_id' => 'exists:approval_item_employee,id',
            'attachments' => 'required|array'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $approval_item_employee = \App\ApprovalItemEmployee::where([
                ['approver_id', $request->input('approver_id')],
                ['id', $request->input('approval_item_employee_id')],
            ])->first();

            if (!$approval_item_employee) {
                throw new \Exception('Approver does not have permission to add an attachment.');
            }

            $approval_item_employee->attachments = $request->input('attachments');
            $approval_item_employee->save();
            \DB::commit();
            return response()->json(['result' => 'success', 'data' => $approval_item_employee], 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'validation_failed', 'messages' => $e->getMessage()], 400);
        }
    }

    public function get_approval_request(Request $request, $approval_request_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $approval_request = \App\ApprovalRequest::with([
            'items.approvers' => function ($q) {
                $q->select('approval_item_employee.id as approval_item_employee_id', 'can_approve', 'remarks', 'attachments', 'approval_item_id', 'approver_id', 'approval_item_employee.updated_at');
            },
            'items.approvers.approver' => function ($q) {
                $q->select('id', 'users_id');
            },
            'items.approvers.approver.personal_information' => function ($q) {
                $q->select('employee_id', 'first_name', 'last_name', 'middle_name');
            },
            'items.approval_level'
        ])
            ->findOrFail($approval_request_id);

        return response()->json(['result' => 'success', 'data' => $approval_request], 200);
    }

    public static function assignNewEmployeeToApprovalFlows($employee) {
        $appFlows = \DB::table('app_flows')
        ->where([
            ['section_id', $employee->employment_and_compensation->section_id],
            ['status', 1],
            ['pick_employee', 0]
        ])
        ->get();

        foreach ($appFlows as $appFlow) {
            \DB::table('app_flow_employee')->insert([
                [
                    'app_flow_id' => $appFlow->id,
                    'requestor_id' => $employee->id,
                    'created_at' => Carbon::now()->toDateString(),
                    'updated_at' => Carbon::now()->toDateString(),
                ]
            ]);
        }
    }
}
