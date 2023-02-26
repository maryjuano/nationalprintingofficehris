<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use \App\Constants;
use \App\Helpers\Days;
use App\TimeOffDetails;
use PDF;

class TimeOffRequestController extends Controller
{
    public function create_or_update(Request $request, \App\TimeOffRequest $time_off_request, $is_new = false)
    {
        if ($is_new) {
            $validator_arr = [
                'time_off_balance_id' => 'required|exists:time_off_balance,id',
                'time_off_type' => 'required|numeric',
                'time_off_details' => 'required',
                'total_days' => 'sometimes|numeric',
            ];
        } else {
            $validator_arr = [];
        }

        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('time_off', $this->me->employee_details->id);
        if ($app_flow_id === -1) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
        }

        if ($request->input('time_off_type') !== 6) {
            $time_off_balance = \App\TimeOffBalance::with([
                'requests' => function ($q) {
                    $q->where([
                        ['status', '!=', -1],
                        ['is_without_pay', false]
                    ]);
                },
                'requests.time_off_details' => function ($q) {
                    $q->whereRaw('YEAR(time_off_date)', Carbon::now()->format('y'));
                },
                'adjustments' => function ($q) {
                    $q->whereRaw('YEAR(effectivity_date)', Carbon::now()->format('y'));
                },
            ])
                ->where([
                    ['id', $request->input('time_off_balance_id')],
                    ['employee_id', $this->me->employee_details->id]
                ])
                ->first();
            if ($time_off_balance == null) {
                return response()->json(['error' => 'validation_failed', 'messages' => 'Employee doesn\'t have selected time off type.'], 400);
            }

            if ($time_off_balance->balance < $request->input('total_days')) {
                if (in_array($request->input('time_off_type'), [1,2])) {
                    $time_off_request->is_without_pay = true;
                } else {
                    return response()->json(['error' => 'validation_failed', 'messages' => 'Employee doesn\'t have enough balance'], 400);
                }
            }
            $time_off_request->balance_at_request_time = $time_off_balance->balance;
        }

        $is_check_attachments = false;
        if ($request->input('time_off_type') === 2) {
            $arguments = [];
            $arguments['absolute_value'] = false;
            if ($request->input('total_days') >= 6) {
                $is_check_attachments = true;
                $check_attachment_message = 'Sick leaves with more than 6 days require an attachment.';
            } else if ($this->get_days_before_start_date_from_details($request->input('time_off_details', []), $arguments) < 0) {
                $is_check_attachments = true;
                $check_attachment_message = 'Sick leaves filed in advance require an attachment.';
            }
        }

        // leave application should be 5 days before leave date
        if ($request->input('time_off_type') === 1) {
            $arguments = [];
            $arguments['include_weekends'] = false;
            $time_option_details = $this->me->employee_details->employment_and_compensation->work_schedule->time_option_details;
            $weekends = [];
            foreach ($time_option_details as $key => $value) {
                if (gettype($value) === 'integer' && $value === 0) {
                    array_push($weekends, $key);
                } else if (array_key_exists('start_time', $value) && $value['start_time'] === null) {
                    array_push($weekends, $key);
                }
            }
            $arguments['weekends'] = $weekends;
            if ($this->get_days_before_start_date_from_details($request->input('time_off_details', []), $arguments) < 5) {
                return response()->json(['error' => 'validation_failed', 'messages' => 'Vacation should be applied 5 working days before'], 400);
            }
            if ($request->input('is_within_Philippines') == 0) {
                // out of the country should be 1 month before
                if ($this->get_days_before_start_date_from_details($request->input('time_off_details', [])) < 30) {
                    return response()->json(['error' => 'validation_failed', 'messages' => 'Out of the Country vacation should be applied 1 month before'], 400);
                }
                // attachments is required
                $is_check_attachments = true;
                $check_attachment_message = 'Out of the Country vacation require attachments.';
            }
        }

        if ($request->input('time_off_type') === 8) {
            $is_check_attachments = true;
            $check_attachment_message = 'Please attach Marriage Contract.';
        }

        if ($request->input('time_off_type') === 10) {
            $is_check_attachments = true;
            $check_attachment_message = 'Please attach photocopy of Solo Parent ID.';
        }

        if ($is_check_attachments) {
            if (!$request->filled('attachments')) {
                return response()->json(['error' => 'validation_failed', 'messages' => $check_attachment_message], 400);
            }
            $attachments = $request->input('attachments', []);
            if (is_array($attachments) && count($attachments) <= 0) {
                return response()->json(['error' => 'validation_failed', 'messages' => $check_attachment_message], 400);
            }
        }

        // required fields
        $time_off_request->employee_id = $this->me->employee_details->id;
        $time_off_request->time_off_balance_id = $request->input('time_off_balance_id');
        $time_off_request->total_days = $request->input('total_days');
        $time_off_request->remarks = $request->input('remarks');
        $time_off_request->status = 0;

        if ($request->has('multiple_days')) {
            $time_off_request->multiple_days = $request->input('multiple_days');
        }

        if ($request->has('is_within_Philippines')) {
            $time_off_request->is_within_Philippines = $request->input('is_within_Philippines');
        }

        if ($request->has('location')) {
            $time_off_request->location = $request->input('location');
        }

        \DB::beginTransaction();
        try {
            if ($is_new) {
                $time_off_request->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                    ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'time_off');

                $time_off_request->save();

                if ($time_off_request->id) {
                    $request_details = $request->input('time_off_details', []);
                    foreach ($request_details as $request_detail) {
                        $time_off_details = new \App\TimeOffDetails();
                        $time_off_details->time_off_request_id = $time_off_request->id;
                        $time_off_details->time_off_date = Carbon::parse($request_detail['time_off_date']);

                        if (array_key_exists('time_off_duration', $request_detail)) $time_off_details->time_off_duration = $request_detail['time_off_duration'];
                        if (array_key_exists('time_will_be_gone', $request_detail)) $time_off_details->time_will_be_gone = $request_detail['time_will_be_gone'];
                        if (array_key_exists('time_off_period', $request_detail)) $time_off_details->time_off_period = $request_detail['time_off_period'];
                        $time_off_details->save();

                        if ($request->input('time_off_type') === 5) {
                            $overtimes_to_use = $request_detail['overtimes_to_use'];
                            if (!$overtimes_to_use) {
                                throw new \Exception('Overtimes not selected for CTO.');
                            }
                            $time_off_duration = $request_detail['time_off_duration'] === 'whole' ? 480 : 240;
                            foreach($overtimes_to_use as $overtime) {
                                $time_off_request->overtime_use()->create([
                                    'overtime_request_id' => $overtime['id'],
                                    'duration_in_minutes' => $time_off_duration > $overtime['minutes'] ? $overtime['minutes'] : $time_off_duration
                                ]);
                                $time_off_duration -= $overtime['minutes'];
                            }
                            if ($time_off_duration > 0) {
                                throw new \Exception('Not enough overtimes selected.');
                            }
                        }
                    }

                    $attachments = $request->input('attachments', []);
                    foreach ($attachments as $attachment) {
                        $document = new \App\Document();
                        $document->employee_id = $this->me->employee_details->id;
                        $document->file_location = $attachment['file_location'];
                        $document->file_name = $attachment['file_name'];
                        $document->file_type = $attachment['file_type'];
                        $document->time_off_request_id = $time_off_request->id;
                        $document->file_date = Carbon::now();
                        $document->save();
                    }

                    $this->log_user_action(
                        Carbon::parse($time_off_request->created_at)->toDateString(),
                        Carbon::parse($time_off_request->created_at)->toTimeString(),
                        $this->me->id,
                        $this->me->name,
                        "Created a Time Off Request",
                        "Self Service"
                    );

                    $time_off_type = $time_off_request->time_off_balance->time_off;

                    \App\Notification::create_hr_notification(
                        ['view_time_off_calendar', 'view_time_off_requests', 'approve_time_off_request', 'monitor_employee_time_off_balances'],
                        $this->me->name . ' applied for ' . $time_off_type->time_off_type . ' on ' . $this->get_dates_from_details($request->input('time_off_details')),
                        \App\Notification::NOTIFICATION_SOURCE_LEAVE,
                        $time_off_request->id,
                        $time_off_request
                    );
                }
            } else {
                $time_off_request->save();
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'validation_failed', 'messages' => $e->getMessage()], 400);
        }

        return response()->json($time_off_request);
    }

    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, new \App\TimeOffRequest(), true);
    }

    public function update(Request $request, \App\TimeOffRequest $time_off_request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->create_or_update($request, $time_off_request);
    }

    public function list_employee_time_offs_upcoming(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\TimeOffRequest::with([
            'time_off_type.color',
            'time_off_details',
            'attachments',
            'approvers' => function ($q) {
                $q->select('approval_item_employee.id as approval_item_employee_id', 'can_approve', 'remarks', 'attachments', 'approval_item_id', 'approver_id', 'approval_item_employee.updated_at');
            },
        ])
            ->whereHas('time_off_details', function ($hasQuery) {
                $hasQuery->where('time_off_date', '>=', Carbon::today()->toDateString());
            })
            ->where([
                ['time_off_requests.employee_id', !$employee_id ? $this->me->employee_details->id : $employee_id],
                ['time_off_requests.status', '1'],
            ]);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_employees_time_off_balance(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $balances = \App\Employee::with([
            'time_off_balances.requests' => function ($q) {
                $q->where('status', '!=', -1);
            },
            'time_off_balances.requests.time_off_details' => function ($q) {
                $q->whereRaw('YEAR(time_off_date)', Carbon::now()->format('y'));
            },
            'time_off_balances.adjustments' => function ($q) {
                $q->whereRaw('YEAR(effectivity_date)', Carbon::now()->format('y'));
            },
            'time_off_balances.time_off'
        ])
            ->where('status', 1)
            ->get();
        $balances->transform(function ($balance) {
            return array(
                'department' => $balance->department,
                'time_off_balances' => $balance->time_off_balances,
                'name' => $balance->name,
                'id' => $balance->id
            );
        });

        return response()->json(array('data' => $balances));
    }

    public function list_employee_time_offs_balance(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $time_off_balance = \App\TimeOffBalance::with([
            'time_off.color',
            'requests' => function ($q) {
                $q->where([
                    ['status', '!=', -1],
                    ['is_without_pay', false]
                ]);
            },
            'requests.time_off_details' => function ($q) {
                $q->whereRaw('YEAR(time_off_date)', Carbon::now()->format('y'));
            },
            'adjustments' => function ($q) {
                $q->whereRaw('YEAR(effectivity_date)', Carbon::now()->format('y'));
            },
        ])
            ->where('employee_id', $employee_id)
            ->get();

        return response()->json($time_off_balance);
    }

    public function list_employee_time_offs_balance_self(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->list_employee_time_offs_balance($request, $this->me->employee_details->id);
    }

    public function adjust_time_off_balance(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'time_off_balance_id' => 'required|unique:time_off_balance',
            'adjustment_value' => 'required|numeric',
            'effectivity_date' => 'required|date',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $time_off = \App\TimeOffAdjustment::create($request->all());

            $this->log_user_action(
                Carbon::parse($time_off->created_at)->toDateString(),
                Carbon::parse($time_off->created_at)->toTimeString(),
                $this->me->id,
                $this->me->name,
                "Adjusted Balance of " . $time_off->time_off_balance_id . ".",
                "HR & Payroll"
            );

            \DB::commit();
            return response()->json($time_off);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function list_employee_time_offs_pending_self(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->list_employee_time_offs_pending($request, $this->me->employee_details->id);
    }

    public function list_employee_time_offs_pending(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\TimeOffRequest::with([
            'time_off_type.color',
            'time_off_details',
            'attachments',
        ])
            ->where([
                ['time_off_requests.employee_id', !$employee_id ? $this->me->employee_details->id : $employee_id],
                ['time_off_requests.status', '0']
            ]);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_employee_time_offs_history_self(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->list_employee_time_offs_history($request, $this->me->employee_details->id);
    }

    public function list_employee_time_offs_history(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\TimeOffRequest::with([
            'time_off_type.color',
            'time_off_details',
            'attachments',
            'approvers' => function ($q) {
                $q->select('approval_item_employee.id as approval_item_employee_id', 'can_approve', 'remarks', 'attachments', 'approval_item_id', 'approver_id', 'approval_item_employee.updated_at');
            },
        ])
            ->where([
                ['time_off_requests.employee_id', $employee_id],
                ['time_off_requests.status', '!=', '0']
            ]);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function list_employee_time_offs_self(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }
        return $this->list_employee_time_offs($request, $this->me->employee_details->id);
    }

    public function list_employee_time_offs(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\TimeOffRequest::with([
            'time_off_type.color',
            'time_off_balance',
            'time_off_details',
            'attachments',
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
                $q->select('id', 'status');
            }
        ])
        ->where('time_off_requests.employee_id', $employee_id)
        ->leftJoin('time_off_balance', 'time_off_balance.id', 'time_off_requests.time_off_balance_id')
        ->leftJoin('time_offs', 'time_offs.id', 'time_off_balance.time_off_id')
        ->select(
            'time_off_requests.*',
            'time_offs.time_off_type'
        );

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereHas('time_off_details', function ($hasQuery) use ($request) {
                $hasQuery->whereBetween('time_off_date', [$request->input('start_date'), $request->input('end_date')]);
            });
        }

        $ALLOWED_FILTERS = ['status'];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function read_time_off_balance(Request $request, $time_off_balance_id)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $time_off_balance = \App\TimeOffBalance::with([
            'requests' => function ($q) {
                $q->where([
                    ['status', '!=', -1],
                    ['is_without_pay', false]
                ]);
            },
            'requests.time_off_details' => function ($q) {
                $q->whereRaw('YEAR(time_off_date)', Carbon::now()->format('y'));
            },
            'adjustments' => function ($q) {
                $q->whereRaw('YEAR(effectivity_date)', Carbon::now()->format('y'));
            },
        ])
            ->where('id', $time_off_balance_id)
            ->first();

        return response()->json(array(
            'result' => 'success',
            'time_off_balance' => $time_off_balance
        ));
    }

    public function read_request_for_approver(Request $request, $time_off_request_id)
    {
        $unauthorized = $this->is_not_authorized(['view_time_off_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $time_off_request = \App\TimeOffRequest::with([
            'requestor',
            'time_off_type.color',
            'time_off_balance',
            'time_off_details',
            'attachments',
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
        //     ->joinSub($approver_list_query, 'approvers', function ($join) {
        //         $join->on('time_off_requests.approval_request_id', '=', 'approvers.approval_request_id');
        //     })
        //     ->leftJoin('employment_and_compensation', 'time_off_requests.employee_id', 'employment_and_compensation.employee_id')
        //     ->leftJoin('profile_picture', 'time_off_requests.employee_id', 'profile_picture.employee_id')
        //     ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
        //     ->leftJoin('personal_information', 'time_off_requests.employee_id', 'personal_information.employee_id')
        //     ->leftJoin('time_off_balance', 'time_off_requests.time_off_balance_id', '=', 'time_off_balance.id')
        //     ->leftJoin('time_offs', 'time_off_balance.time_off_id', '=', 'time_offs.id')
        //     ->leftJoin('time_off_color', 'time_offs.time_off_color_id', '=', 'time_off_color.id')
        ->where('time_off_requests.id', $time_off_request_id)
        //     ->select(
        //         'time_off_requests.*',
        //         'time_offs.time_off_type as time_off_type',
        //         'time_off_color.color_hex as time_off_color',
        //         \DB::raw('CONCAT(
        //     IFNULL(personal_information.last_name, \'\'),
        //         \', \',
        //         IFNULL(personal_information.first_name, \'\'),
        //         \' \',
        //         IFNULL(personal_information.middle_name, \'\')
        // ) as name'),
        //         'departments.department_name',
        //         'profile_picture.file_location as picture'
        //     )
            ->first();

        return response()->json(array(
            'result' => 'success',
            'time_off_request' => $time_off_request
        ));
    }

    public function list_requests_for_approver(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_time_off_requests']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $query = \App\TimeOffRequest::with([
            'requestor',
            'time_off_type.color',
            'time_off_balance',
            'time_off_details',
            'attachments',
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
                $join->on('time_off_requests.approval_request_id', '=', 'approvers.approval_request_id');
            })
            // ->leftJoin('employment_and_compensation', 'time_off_requests.employee_id', 'employment_and_compensation.employee_id')
            // ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            // ->leftJoin('personal_information', 'time_off_requests.employee_id', 'personal_information.employee_id')
            // ->leftJoin('time_off_balance', 'time_off_requests.time_off_balance_id', '=', 'time_off_balance.id')
            // ->leftJoin('time_offs', 'time_off_balance.time_off_id', '=', 'time_offs.id')
            // ->leftJoin('time_off_color', 'time_offs.time_off_color_id', '=', 'time_off_color.id')
            ->where('approvers.can_approve', '=', '1')
            // ->select(
            //     'time_off_requests.*',
            //     'approvers.approval_date',
            //     //'time_offs.time_off_type as time_off_type',
            //     'time_off_color.color_hex as time_off_color',
            //     \DB::raw('CONCAT(
            //     IFNULL(personal_information.last_name, \'\'),
            //         \', \',
            //         IFNULL(personal_information.first_name, \'\'),
            //         \' \',
            //         IFNULL(personal_information.middle_name, \'\')
            // ) as name'),
            //     'departments.department_name'
            // )
            ;

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereHas('time_off_details', function ($hasQuery) use ($request) {
                $hasQuery->whereBetween('time_off_date', [$request->input('start_date'), $request->input('end_date')]);
            });
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
        $unauthorized = $this->is_not_authorized(['approve_time_off_request']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'time_off_requests' => 'required|array',
            'time_off_requests.*' => 'exists:time_off_requests,id',
            'status' => 'required|in:-1,1'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $result_time_off_requests = array();
            foreach ($request->input('time_off_requests') as $time_off_request_id) {
                $time_off_request = \App\TimeOffRequest::find($time_off_request_id);
                $time_off_date = \App\TimeOffDetails::select('time_off_date')
                    ->where('time_off_request_id', $time_off_request_id)
                    ->first();

                $result = app('\App\Http\Controllers\AppFlowController')->approve_reject_request($time_off_request->approval_request_id, $this->me->employee_details->id, $request->input('status'));
                if ($result === 'request_approved' || $result === 'request_rejected') {
                    $time_off_request->status = $result === 'request_approved' ? 1 : -1;
                    $time_off_request->save();

                    $time_off_type = $time_off_request->time_off_balance->time_off;

                    \App\Notification::create_user_notification(
                        $time_off_request->requestor->users_id,
                        'Your request for ' . $time_off_type->time_off_type . ' on ' . $this->get_dates_from_details($time_off_request->time_off_details) . ' is ' . ($result === 'request_approved' ? 'Approved' : 'Declined'),
                        \App\Notification::NOTIFICATION_SOURCE_LEAVE,
                        $time_off_request->id,
                        $time_off_request
                    );
                }
                array_push($result_time_off_requests, array(
                    'id' => $time_off_request->id,
                    'status' => $time_off_request->status,
                ));
            }

            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $result_time_off_requests));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(array(
                "error" => "Request Failed!",
                "message" => $e->getMessage()
            ), 400);
        }
    }

    public function read_pdf(Request $request, \App\TimeOffRequest $time_off_request)
    {
        view()->share('time_off_request', $time_off_request);
        $employee = \App\Employee::with([
            'employment_and_compensation'
        ])->where('employees.id', $time_off_request->employee_id)->first();
        $time_off_balance = \App\TimeOffBalance::with([
            'time_off.color',
            'requests' => function ($q) use ($time_off_request) {
                $q->where([
                    ['status', '!=', -1],
                    ['is_without_pay', false]
                ])
                ->whereRaw("created_at < '" . $time_off_request->created_at . "'");
            },
            'requests.time_off_details' => function ($q) use ($time_off_request) {
                $q->whereRaw('YEAR(time_off_date)', Carbon::now()->format('y'))
                    ->whereRaw("created_at < '" . $time_off_request->created_at . "'");
            },
            'adjustments' => function ($q) use ($time_off_request) {
                $q->whereRaw('YEAR(effectivity_date)', Carbon::now()->format('y'))
                    ->whereRaw("created_at < '" . $time_off_request->created_at . "'");
            },
        ])
            ->where('employee_id', $employee->id)
            ->get();
        view()->share('employee', $employee);
        $time_off_balance_dict = array();
        foreach ($time_off_balance as $balance) {
            $time_off_balance_dict[$balance->time_off->time_off_code] = $balance;
        }

        view()->share('time_off_balance', $time_off_balance_dict);

        if ($time_off_request->time_off_type->time_off_code == 'CTO') {
            // cto
            $pdf = PDF::loadView('pdf.cto')->setPaper('A4', 'portrait');
            return $pdf->stream('cto.pdf');
        } else {
            // normal leave request (SL/VL others)
            $pdf = PDF::loadView('pdf.application_for_leave')->setPaper('A4', 'portrait');
            return $pdf->stream('application_for_leave.pdf');
            // return view('pdf.application_for_leave');
        }
    }

    public function getTimeOffEndDate(Request $request) {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'start_date' => 'required|date_format:Y-m-d',
            'include_weekends' => 'required|boolean',
            'total_days' => 'required|numeric',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $dayInWeek = Days::DayInWeek;
        $timeOption = $this->me->employee_details->employment_and_compensation->work_schedule->time_option;
        $timeOptionDetails = $this->me->employee_details->employment_and_compensation->work_schedule->time_option_details;
        $weekends = [];
        $holidays = \App\Holiday::query()
        ->where('is_recurring', 1)
        ->orWhere('date', '>=', $request->input('start_date'))
        ->get()
        ->map(function ($holiday) {
            return (int) Carbon::createFromFormat('Y-m-d', $holiday->date)->format('md');
        });

        if (!$request->input('include_weekends')) {
            if (in_array($timeOption, [3,4])) {
                array_push($weekends, array_search('sunday', $dayInWeek), array_search('saturday', $dayInWeek));
            } else if (in_array($timeOption, [1,2])) {
                foreach ($timeOptionDetails as $key => $value) {
                    if ($timeOption === 2 && $value['start_time'] === null) {
                        array_push($weekends, array_search($key, $dayInWeek));
                    } else if ($timeOption === 1 && $value === 0) {
                        array_push($weekends, array_search($key, $dayInWeek));
                    }
                }
            }
        }

        $daysRemaining = $request->input('total_days') - 1; // - 1 for the startDate
        $endDate = Carbon::createFromFormat('Y-m-d', $request->input('start_date'));
        while ($daysRemaining > 0) {
            $endDate->addDays(1);
            $isWeekend = in_array($endDate->dayOfWeek, $weekends);
            $endDateDayMonth = (int) $endDate->format('md');
            $isHoliday = !$request->input('include_weekends') ? $holidays->contains(function ($holiday) use ($endDateDayMonth) {
                return $holiday === $endDateDayMonth;
            }) : false;
            if (!$isWeekend) {
                $daysRemaining--;
            }
        }

        return response()->json(['end_date' => $endDate->format('Y-m-d')]);
    }

    public function getHolidaysAndDaysOff(Request $request) {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $holidays = \App\Attendance::getHolidays($request->input('start_date'), $request->input('end_date'));
        $days = \App\Attendance::getFormattedDtrDays($request->input('start_date'), $request->input('end_date'), $holidays);

        return response()->json(['days' => $days, 'start_date' => $request->input('start_date'), 'end_date' => $request->input('end_date')]);
    }

    private function get_days_before_start_date_from_details($request_details, $args = null) {
        $start_date = null;
        foreach ($request_details as $request_detail) {
            $time_off_date = Carbon::parse($request_detail['time_off_date']);
            if ($start_date == null || $start_date > $time_off_date) {
                $start_date = $time_off_date;
            }
        }

        $getAbsoluteValue = $args['absolute_value'] ?? true;
        $includeWeekends = $args['include_weekends'] ?? true;
        $weekends = $args['weekends'] ?? [];

        if ($start_date == null) return 99;
        else return $start_date->diffInDaysFiltered(function (Carbon $date) use ($includeWeekends, $weekends) {
            if ($includeWeekends) {
                return true;
            }
            $dayNow = strtolower($date->englishDayOfWeek);
            return !in_array($dayNow, $weekends);
        }, Carbon::now(), $getAbsoluteValue);
    }

    private function get_dates_from_details($request_details) {
        $start_date = null;
        $end_date = null;
        foreach ($request_details as $request_detail) {
            $time_off_date = Carbon::parse($request_detail['time_off_date']);
            if ($start_date == null || $start_date > $time_off_date) {
                $start_date = $time_off_date;
            }
            if ($end_date == null || $end_date < $time_off_date) {
                $end_date = $time_off_date;
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
