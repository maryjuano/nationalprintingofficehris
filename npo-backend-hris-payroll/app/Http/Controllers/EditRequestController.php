<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class EditRequestController extends Controller
{
    public function create_or_update(Request $request, \App\EditRequest $edit_request, $is_new = false)
    {
        $validator_arr = [
            'tab' => 'required',
            'old' => 'required',
            'new' => 'required',
            'purpose' => 'required',
            'attachments' => 'sometimes|array'
        ];

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        if ($request->input('tab') == "General Information" or $request->input('tab') == "System Information") {
            $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('employee_info', $this->me->employee_details->id);
            if ($app_flow_id === -1) {
                return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
            }
        } else if ($request->input('tab') == "Employment and Compensation") {
            $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('employee_schedule', $this->me->employee_details->id);
            if ($app_flow_id === -1) {
                return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
            }
        }

        //required fields
        $edit_request->employee_id = $this->me->employee_details->id;
        $edit_request->department_id = (int) $this->me->division;
        $edit_request->tab = $request->input('tab');
        $edit_request->old = $request->input('old');
        $edit_request->new = $request->input('new');
        $edit_request->purpose = $request->input('purpose');

        //not required fields
        $edit_request->status = 0;
        $edit_request->attachments = count($request->input('attachments', [])) > 0 ? $request->input('attachments') : [];

        \DB::beginTransaction();
        try {
            if ($is_new) {
                if ($request->input('tab') == "General Information" or $request->input('tab') == "System Information") {
                    $edit_request->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                        ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'employee_info');
                } else if ($request->input('tab') == "Employment and Compensation") {
                    $edit_request->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                        ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'employee_schedule');
                }
                $edit_request->save();
                $this->saveAttachments(
                    $edit_request->attachments,
                    $request->input('purpose'),
                    $this->me->employee_details->id,
                    $edit_request->id
                );
                $this->log_user_action(
                    Carbon::parse($edit_request->created_at)->toDateString(),
                    Carbon::parse($edit_request->created_at)->toTimeString(),
                    $this->me->id,
                    $this->me->name,
                    "Created an Edit Request for" . $edit_request->tab,
                    "Self Service"
                );
                \App\Notification::create_hr_notification(
                    ['view_information_request', 'approve_information_request', 'view_schedule_requests', 'approve_schedule_request'],
                    $this->me->name . ' requested for change of ' . $edit_request->tab,
                    \App\Notification::NOTIFICATION_SOURCE_INFO,
                    $edit_request->id,
                    $edit_request
                );

            } else {
                $edit_request->updated_by = $this->me->id;
                $edit_request->save();
            }
            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $edit_request));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => "Sorry! There was a problem saving your requests. " . $e->getMessage()
            ), 400);
        }
    }
    private function saveAttachments($attachments, $purpose, $employee_id, $edit_req_id)
    {
        foreach ($attachments as $attachments) {
            \App\Document::create([
                'file_location' => $attachments['url'],
                'file_name' => $attachments['name'],
                'file_type' => $attachments['type'],
                'file_date' => Carbon::now(),
                'file_remarks' => $purpose,
                'employee_id' => $employee_id,
                'edit_req_id' => $edit_req_id
            ]);
        }
    }
    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, new \App\EditRequest(), true);
    }

    public function update(Request $request, \App\EditRequest $edit_request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, $edit_request);
    }

    public function list_user_edits(Request $request, \App\User $user)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\EditRequest::with([
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
            ->where('employee_id', $user->employee_details->id);

        $ALLOWED_FILTERS = ['tab', 'status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_requests_for_approver(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_information_request']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $query = \App\EditRequest::with([
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
                $join->on('edit_requests.approval_request_id', '=', 'approvers.approval_request_id');
            })
            ->leftJoin('employment_and_compensation', 'edit_requests.employee_id', 'employment_and_compensation.employee_id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('personal_information', 'edit_requests.employee_id', 'personal_information.employee_id')
            ->where('approvers.can_approve', '=', '1')
            ->select(
                'edit_requests.*',
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
        $unauthorized = $this->is_not_authorized(['approve_information_request']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'edit_requests' => 'required|array',
            'edit_requests.*' => 'exists:edit_requests,id',
            'status' => 'required|in:-1,1'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $result_edit_requests = array();
            foreach ($request->input('edit_requests') as $edit_request_id) {
                $edit_request = \App\EditRequest::find($edit_request_id);
                $result = app('\App\Http\Controllers\AppFlowController')->approve_reject_request($edit_request->approval_request_id, $this->me->employee_details->id, $request->input('status'));
                if ($result === 'request_approved' || $result === 'request_rejected') {
                    $edit_request->status = $result === 'request_approved' ? 1 : -1;
                    $edit_request->save();
                    $employee = \App\Employee::where('id', $edit_request->employee_id)->first();
                    \App\Notification::create_user_notification(
                        $employee->users_id,
                        'Your request for ' . $edit_request->tab . ' change is ' . ($result === 'request_approved' ? 'Approved' : 'Declined'),
                        \App\Notification::NOTIFICATION_SOURCE_INFO,
                        $edit_request->id,
                        $edit_request
                    );

                }
                if ($result === 'request_approved') {
                    $this->apply_request($edit_request);
                }
                array_push($result_edit_requests, array(
                    'id' => $edit_request->id,
                    'status' => $edit_request->status,
                ));
            }
            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $result_edit_requests));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => $e->getMessage()
            ), 400);
        }
    }

    private function apply_request(\App\EditRequest $edit_request)
    {
        foreach ($edit_request->new as $module_container) {
            foreach ($module_container as $key => $value) {
                if ($key === 'employment_and_compensation') {
                    $this->apply_work_schedule_change($edit_request->employee_id, $value);
                } else if ($key === 'system_information') {
                    $this->apply_system_information($edit_request->employee_id, $value);
                } else {
                    $this->apply_general_change($edit_request->employee_id, $value);
                }
            }
        }
    }

    private function apply_general_change($employee_id, $changes)
    {
        foreach ($changes as $key => $value) {
            if ($key === 'personal_information' || $key === 'family_background') {
                if ($key === 'personal_information') {
                    $model =  \App\PersonalInformation::where('employee_id', $employee_id)->first();
                    foreach ($value as $key => $item) {
                        if ($key === 'mobile_number') {
                            $this->logInfoChange($employee_id, 'personal_information', $key, implode(",", $model->$key), implode(",", $item));
                            continue;
                        }
                        $this->logInfoChange($employee_id, 'personal_information', $key, $model->$key, $item);
                    }
                } else {
                    $model =  \App\FamilyBackground::where('employee_id', $employee_id)->first();
                }

                if (!$model) {
                    throw new \Exception('Employee data not found');
                }

                $model->fill($value);
                $model->save();
                continue;
            }


            if ($key === 'work_experience') {
                $current = \App\WorkExperience::where('employee_id', $employee_id)->get();
                foreach ($value as $data) {
                    if (isset($data['id'])) {
                        $model = $current->firstWhere('id', $data['id']);
                    }
                    if (!$model) {
                        $model = new \App\WorkExperience();
                        $model->employee_id = $employee_id;
                    }
                    $model->fill($data);
                    $model->save();
                }
            } else if ($key === 'educational_background') {
                $current = \App\EducationalBackground::where('employee_id', $employee_id)->get();
                foreach ($value as $data) {
                    if (isset($data['id'])) {
                        $model = $current->firstWhere('id', $data['id']);
                    }
                    if (!$model) {
                        $model = new \App\EducationalBackground();
                        $model->employee_id = $employee_id;
                    }
                    $model->fill($data);
                    $model->save();
                }
            } else if ($key === 'civil_service') {
                $current = \App\CivilService::where('employee_id', $employee_id)->get();
                foreach ($value as $data) {
                    if (isset($data['id'])) {
                        $model = $current->firstWhere('id', $data['id']);
                    }
                    if (!$model) {
                        $model = new \App\CivilService();
                        $model->employee_id = $employee_id;
                    }
                    $model->fill($data);
                    $model->save();
                }
            } else if ($key === 'training_program') {
                $current = \App\TrainingProgram::where('employee_id', $employee_id)->get();
                foreach ($value as $data) {
                    if (isset($data['id'])) {
                        $model = $current->firstWhere('id', $data['id']);
                    }
                    if (!$model) {
                        $model = new \App\TrainingProgram();
                        $model->employee_id = $employee_id;
                    }
                    $model->fill($data);
                    $model->save();
                }
            } else if ($key === 'voluntary_work') {
                $current = \App\VoluntaryWork::where('employee_id', $employee_id)->get();
                foreach ($value as $data) {
                    if (isset($data['id'])) {
                        $model = $current->firstWhere('id', $data['id']);
                    }
                    if (!$model) {
                        $model = new \App\VoluntaryWork();
                        $model->employee_id = $employee_id;
                    }
                    $model->fill($data);
                    $model->save();
                }
            } else if ($key === 'other_information') {
                $current = \App\OtherInformation::where('employee_id', $employee_id)->get();
                foreach ($value as $data) {
                    if (isset($data['id'])) {
                        $model = $current->firstWhere('id', $data['id']);
                    }
                    if (!$model) {
                        $model = new \App\OtherInformation();
                        $model->employee_id = $employee_id;
                    }
                    $model->fill($data);
                    $model->save();
                }
            }
        }
    }

    private function apply_system_information($employee_id, $changes)
    {
        $system_information =  \App\SystemInformation::where('employee_id', $employee_id)->first();

        if (!$system_information) {
            throw new \Exception('Employee data not found');
        }

        $system_information->fill($changes);
        $system_information->save();

        // change email in users table
        $user_information = \App\User::join('employees', 'users.id', 'employees.users_id')
            ->where('employees.id', $employee_id)
            ->select('users.*')
            ->first();

        if (!$user_information) {
            throw new \Exception('Employee data not found');
        }
        $user_information->fill($changes);
        $user_information->save();

        // change in login email
        try {
            $client = new \GuzzleHttp\Client();
            $client->put(config('app.UPDATE_USER_URL') . $user_information->id, [\GuzzleHttp\RequestOptions::JSON => $changes], ['http_errors' => false]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new \Exception('Could not change employee email');
        }
    }

    private function apply_work_schedule_change($employee_id, $changes)
    {
        $employment_and_compensation =  \App\EmploymentAndCompensation::where('employee_id', $employee_id)->first();

        if (!$employment_and_compensation) {
            throw new \Exception('Employee data not found');
        }

        $employment_and_compensation->fill($changes);
        $employment_and_compensation->save();
    }
}
