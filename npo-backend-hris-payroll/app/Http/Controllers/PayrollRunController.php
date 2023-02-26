<?php

namespace App\Http\Controllers;

use App\Adjustment;
use App\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;


class PayrollRunController extends Controller
{

    const LOG_TYPE_EARNINGS = 0;
    const LOG_TYPE_DEDUCTIONS = 1;
    const LOG_TYPE_CONTRIBUTIONS = 2;
    const LOG_TYPE_TAXES = 3;
    const LOG_TYPE_LOANS = 4;
    const LOG_TYPE_REIMBURSEMENTS = 5;
    const LOG_TYPE_EARNINGS_OVERPAID = 101;
    const LOG_TYPE_EARNINGS_UNDERPAID = 102;
    const LOG_TYPE_CONTRIBUTIONS_ARREAR = 201;
    const LOG_TYPE_CONTRIBUTIONS_REFUND = 202;
    const LOG_TYPE_LOANS_REFUND = 402;


    const STATUS_DRAFT = 0;
    const STATUS_SIMULATED = 1;
    const STATUS_COMPLETED = 2;

    const RUN_TYPE_REGULAR = 0;
    const RUN_TYPE_CONTRACTUAL = 1;
    const RUN_TYPE_OFFCYCLE = 2;
    const RUN_TYPE_OVERTIME = 3;

    const PAYROLL_TYPE_DAILY = 0;
    const PAYROLL_TYPE_SEMI_MONTHLY = 1;
    const PAYROLL_TYPE_MONTHLY = 2;

    public function create(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['run_payroll']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'payroll_name' => 'required|unique:payroll_run',
            'run_type' => 'required',
            'payroll_type' => 'required',
            'payroll_period_start' => 'required',
            'payroll_period_end' => 'required',
            'payroll_date' => 'required',
            'employee_ids' => 'required',
            'pay_structure' => 'required',
            'status' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $payroll_run = \App\Payrun::create($request->only([
                'payroll_name',
                'payroll_type',
                'payroll_period_start',
                'payroll_period_end',
                'deduction_start',
                'deduction_end',
                'employee_ids',
                'payroll_date',
                'run_type',
                'pay_structure',
                'days_in_month',
                'adjustments'
            ]));
            $payroll_run->status = $request->input('status');
            $payroll_run->created_by = $this->me->employee_details->id;
            $payroll_run->emp_count = count($request->input('employee_ids', []));
            $payroll_run->save();
            \DB::commit();
            return response()->json($payroll_run);
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }

    public function read(\App\Payrun $payrun)
    {
        $unauthorized = $this->is_not_authorized(['view_payrun']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $payrun->adjustments_obj = $payrun->adjustments_obj;

        return response()->json($payrun);
    }

    public function update(Request $request, \App\Payrun $payrun)
    {
        $unauthorized = $this->is_not_authorized(['run_payroll']);
        if ($unauthorized) {
            return $unauthorized;
        }

        \DB::beginTransaction();
        try {
            $payrun->fill($request->only([
                'payroll_name',
                'payroll_type',
                'payroll_period_start',
                'payroll_period_end',
                'deduction_start',
                'deduction_end',
                'employee_ids',
                'payroll_date',
                'run_type',
                'pay_structure',
                'adjustments',
            ]));
            $payrun->status = $request->input('status');
            $payrun->save();

            // finalize
            if ($payrun->status === self::STATUS_COMPLETED) {
                $this->save_payroll_logs($payrun);

                foreach ($payrun->pay_structure as $employee_data) {
                    foreach ($employee_data['loans'] as $loan) {
                        if (!isset($loan['loan_id'])) {
                            continue;
                        }
                        $loan_payment = new \App\LoanPayment();
                        $loan_payment->payrun_id = $payrun->id;
                        $loan_payment->loan_request_id = $loan['loan_id'];
                        $loan_payment->amount = $loan['amount'];
                        $loan_payment->save();
                    }

                    // negative loan_payment for loan_refunds
                    foreach ($employee_data['loans_refund'] as $loan) {
                        if (!isset($loan['loan_id'])) {
                            continue;
                        }
                        $loan_payment = new \App\LoanPayment();
                        $loan_payment->payrun_id = $payrun->id;
                        $loan_payment->loan_request_id = $loan['loan_id'];
                        $loan_payment->amount = -$loan['amount'];
                        $loan_payment->save();
                    }
                    // handle overtime and create overtime uses

                    if ($payrun->run_type == self::RUN_TYPE_OVERTIME) {
                        $total_minutes = 0;
                        foreach ($employee_data['overtime_requests'] as $overtime_request) {
                            if ($overtime_request['duration_in_minutes'] > 0) {
                                $use = new \App\OvertimeUse();
                                $use->overtime_request_id = $overtime_request['id'];
                                $use->duration_in_minutes = $overtime_request['duration_in_minutes'];
                                $total_minutes += $overtime_request['duration_in_minutes'];
                                $use->save();
                                $payrun->overtime_use()->save($use);
                            }
                        }
                        if ($total_minutes > 0) {
                            $cto_balance = \App\TimeOffBalance::where([
                                ['employee_id', $employee_data['employee_id']],
                                ['time_off_id', \App\TimeOff::TYPE_CTO]
                            ])
                            ->first();

                            \App\TimeOffAdjustment::create([
                                'time_off_balance_id' => $cto_balance->id,
                                'adjustment_value' => -1 * round($total_minutes / 60, 2),
                                'effectivity_date' => Carbon::now()->toDateString(),
                                'remarks' => 'Payrun on ' . $payrun->payroll_date
                            ]);

                        }
                    }
                }


            }

            \DB::commit();
            return response()->json($payrun);
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }

    public function update_details(Request $request, \App\Payrun $payrun)
    {
        $unauthorized = $this->is_not_authorized(['run_payroll']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'title' => 'sometimes|string',
            'subtitle' => 'sometimes|array',
            'subtitle.*' => 'string|nullable',
            'bur_dv_description' => 'sometimes|string',
        ];

        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $payrun->fill($request->only([
                'title',
                'subtitle',
                'bur_dv_description'
            ]));
            $payrun->save();

            \DB::commit();
            return response()->json($payrun);
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }

    private function save_payroll_logs($payrun)
    {
        $pay_structure = $payrun ? $payrun->pay_structure : array();

        foreach ($pay_structure as $datum) {
            if (is_array($datum)) {
                $datum = (object) $datum;
            }

            $adjustments = \App\Adjustment::all();
            $adjustment_lookup = $adjustments->keyBy('adjustment_name');
            $this->save_payroll_log($datum->earnings, $payrun, $this::LOG_TYPE_EARNINGS, $datum, $adjustment_lookup);
            $this->save_payroll_log($datum->deductions, $payrun, $this::LOG_TYPE_DEDUCTIONS, $datum, $adjustment_lookup);
            $this->save_payroll_log($datum->contributions, $payrun, $this::LOG_TYPE_CONTRIBUTIONS, $datum, $adjustment_lookup);
            $this->save_payroll_log($datum->taxes, $payrun, $this::LOG_TYPE_TAXES, $datum, $adjustment_lookup);
            $this->save_payroll_log($datum->reimbursements, $payrun, $this::LOG_TYPE_REIMBURSEMENTS, $datum, array());
            $this->save_payroll_log($datum->loans, $payrun, $this::LOG_TYPE_LOANS, $datum, array());

            $this->save_payroll_log($datum->earnings_overpaid, $payrun, $this::LOG_TYPE_EARNINGS_OVERPAID, $datum, $adjustment_lookup);
            $this->save_payroll_log($datum->contributions_arrear, $payrun, $this::LOG_TYPE_CONTRIBUTIONS_ARREAR, $datum, $adjustment_lookup);

            $this->save_payroll_log($datum->earnings_underpaid, $payrun, $this::LOG_TYPE_EARNINGS_UNDERPAID, $datum, $adjustment_lookup);
            $this->save_payroll_log($datum->contributions_refund, $payrun, $this::LOG_TYPE_CONTRIBUTIONS_REFUND, $datum, $adjustment_lookup);
            $this->save_payroll_log($datum->loans_refund, $payrun, $this::LOG_TYPE_LOANS_REFUND, $datum, array());
        }
    }

    private function save_payroll_log($type_list, $payrun, $type_of, $log_datum, $type_of_lookup)
    {
        foreach ($type_list as $datum) {
            $table = new \App\PayrollEmployeeLog();
            $table->payroll_id = $payrun->id;
            $table->payroll_period = $payrun->payroll_date;
            $table->payroll_start = $payrun->payroll_period_start;
            $table->payroll_end = $payrun->payroll_period_end;
            $table->day = Carbon::parse($payrun->payroll_date)->format('d');
            $table->month = Carbon::parse($payrun->payroll_date)->format('m');
            $table->year =  Carbon::parse($payrun->payroll_date)->format('Y');

            $table->inclusion_type = array();
            $table->gross_pay = $log_datum->gross_pay;
            $table->basic_pay = $log_datum->basic_pay;
            $table->net_pay = $log_datum->net_pay;
            $table->employee_id = $log_datum->employee_id;
            $table->type_of = $type_of;

            $datum = (object) $datum;
            $table->type_of_string = $datum->title;
            if (isset($type_of_lookup[$datum->title])) {
                $table->type_of_id = $type_of_lookup[$datum->title]->id;
            }
            $table->amount = $datum->amount;
            if (isset($datum->company_share)) {
                $table->company_share = $datum->company_share;
            }
            if (isset($datum->gsis_ecc)) {
                $table->gsis_ecc = $datum->gsis_ecc;
            }
            $table->save();
        }
    }



    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_payrun']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\Payrun::select('*');
        $ALLOWED_FILTERS = ['payroll_type', 'run_type', 'status'];
        $SEARCH_FIELDS = ['payroll_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function employee_list_payroll(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['run_payroll']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'payroll_name' => 'required',
            'run_type' => 'required|numeric',
            'payroll_type' => 'required|numeric',
            'payroll_period_start' => 'required',
            'payroll_period_end' => 'required',
            'payroll_date' => 'required',
            // 'deduction_end' => 'required',
            // 'deduction_start' => 'required',
            // 'days_in_month' => 'required|numeric',
            // 'adjustments' => 'required|array'
        ];

        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $all_adjustments = \App\Adjustment::all();
        $adjustment_lookup = $all_adjustments->keyBy('adjustment_name');
        $napowa_id = $adjustment_lookup[\App\Adjustment::CONST_NAPOWA_DUE]->id;
        $pera_allowance_id = $adjustment_lookup[\App\Adjustment::CONST_PERA_ALLOWANCE]->id;
        $adjustments = [];
        if (request('run_type') == self::RUN_TYPE_REGULAR) {
            $include_dtr = true;
            $include_basic = true;
            $include_loans = true;
            $include_reimbursements = true;
            $include_overtime = false;
            $include_night_differential = true;
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_PERA_ALLOWANCE]->id);
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_TAX]->id);
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_PAG_IBIG]->id);
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_PHILHEALTH]->id);
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_GSIS]->id);
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_NAPOWA_DUE]->id);
        }
        else if (request('run_type') == self::RUN_TYPE_CONTRACTUAL) {
            $include_dtr = true;
            $include_loans = true;
            $include_basic = true;
            $include_reimbursements = false;
            $include_overtime = false;
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_PREMIUM]->id);
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_TAX_TWO_PERCENT]->id);
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_TAX_THREE_PERCENT]->id);
        }
        else if (request('run_type') == self::RUN_TYPE_OFFCYCLE) {
            $include_dtr = false;
            $include_basic = false;
            $include_loans = false;
            $include_reimbursements = false;
            $include_overtime = false;
            $adjustments = request('adjustments', []);
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_TAX]->id);
        }
        else if (request('run_type') == self::RUN_TYPE_OVERTIME) {
            $include_dtr = false;
            $include_basic = false;
            $include_loans = false;
            $include_reimbursements = false;
            $include_overtime = true;
            array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_TAX]->id);
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $deduction_start = $request->input('deduction_start');
        $deduction_end = $request->input('deduction_end');

        $employees_query = \App\EmployeeStubList::with([
            'latest_pagibig_request',
            'loan_requests' => function ($q) {
                $q->leftJoin('loans', 'loans.id', 'loan_requests.loan_type_id')
                ->where('loan_requests.status', 1)
                ->whereRaw('
                    loan_requests.loan_amount > (
                        SELECT IFNULL(SUM(loan_payments.amount), 0)
                        FROM loan_payments
                        WHERE loan_payments.loan_request_id = loan_requests.id
                    )
                ')
                ->select(
                    'loan_requests.*',
                    'loans.category'
                );
            },
            'salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            },
            'dtrs' => function ($q) use ($deduction_start, $deduction_end) {
                $q->whereBetween('dtr_date', [$deduction_start, $deduction_end]);
                $q->leftJoin('dtr_submits', 'dtr_submits.id', 'dtrs.dtr_submit_id');
                $q->where('dtr_submits.status', 3);
                $q->select(
                    'dtrs.employee_id',
                    'dtrs.absence',
                    'dtrs.undertime_minutes',
                    'dtrs.late_minutes',
                    'dtrs.night_differential_minutes',
                    'dtrs.absence_for_payment_deduction',
                    'dtrs.late_for_payment_deduction',
                    'dtr_submits.status'
                );
            },
            'overtime_requests' => function ($q) use ($deduction_start, $deduction_end) {
                // add 23 hours to $deduction_end
                $end = Carbon::parse($deduction_end)->addHours(23)->format('Y-m-d H:i:s');
                $q->whereBetween('dtr_date', [$deduction_start, $end]);
                $q->where('status', \App\OvertimeRequest::STATUS_APPROVED);
            },
            'employment_type'
        ]);

        if (request('run_type') == self::RUN_TYPE_CONTRACTUAL) {
            $employees_query = $employees_query->whereIn('employee_type_id', [
                \App\EmployeeType::COS,
                \App\EmployeeType::JOB_ORDER
            ]);
        }
        else if (request('run_type') == self::RUN_TYPE_REGULAR) {
            $employees_query = $employees_query->whereNotIn('employee_type_id', [
                \App\EmployeeType::COS,
                \App\EmployeeType::JOB_ORDER
            ]);
        }

        $ALLOWED_FILTERS = ['department_name', 'position_name', 'employee_type_name'];
        $SEARCH_FIELDS = ['first_name', 'last_name', 'middle_name', 'section_name', 'department_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($employees_query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS, "employee_id");
        $employees_stubs = $response['data'];
        $response['adjustments'] = $adjustments;
        $response['adjustment_lookup'] = $all_adjustments->keyBy('id');

        $config_philhealth = $this->get_statutory_config_data('philhealth');
        $config_gsis = $this->get_statutory_config_data('gsis');
        $config_pagibig = $this->get_statutory_config_data('pagibig');

        if ($include_overtime) {
            $time_data_lookup = $this->get_time_data_lookup($deduction_start, $deduction_end);
            $response['time_data_lookup'] = $time_data_lookup;
        }

        foreach ($employees_stubs as $employee_stub) {
            $new_employee_stub = $employee_stub;
            $employee_is_temporary =
                $employee_stub->employment_type->employee_type_id === \App\EmployeeType::COS ||
                $employee_stub->employment_type->employee_type_id === \App\EmployeeType::JOB_ORDER;
            $salary_with_step = $employee_is_temporary ?
                $employee_stub->employment_type->salary_rate :
                $employee_stub->salary->step[$employee_stub->step_increment ?? 0];
            if ($salary_with_step == null) {
                $salary_with_step = 0;
            }

            $employee_stub_copy = array();
            $employee_stub_copy['earnings'] = $employee_stub['earnings'] ?? [];
            $employee_stub_copy['loans'] = $employee_stub['loans'] ?? [];
            $employee_stub_copy['deductions'] = $employee_stub['deductions'] ?? [];
            $employee_stub_copy['contributions'] = $employee_stub['contributions'] ?? [];
            $employee_stub_copy['reimbursements'] = $employee_stub['reimbursements'] ?? [];
            $employee_stub_copy['taxes'] = [];
            $employee_stub_copy['latest_pagibig_request'] = $employee_stub['latest_pagibig_request'];
            $employee_stub_copy['updated_at'] = $employee_stub['updated_at'];
            $employee_stub_copy['loan_requests'] = $employee_stub['loan_requests'];


            $new_employee_stub['earnings'] = [];
            $new_employee_stub['loans'] = [];
            $new_employee_stub['deductions'] = [];
            $new_employee_stub['contributions'] = [];
            $new_employee_stub['reimbursements'] = [];
            $new_employee_stub['taxes'] = [];

            $new_employee_stub['earnings_overpaid'] = [];
            $new_employee_stub['earnings_underpaid'] = [];
            $new_employee_stub['contributions_arrear'] = [];
            $new_employee_stub['contributions_refund'] = [];
            $new_employee_stub['loans_refund'] = [];

            $new_employee_stub['basic_pay'] = $salary_with_step; // only used for informational purposes
            $basic_earnings = $this->get_earnings(
                $salary_with_step,
                $request->input('payroll_type'),
                $request->input('days_in_month')
            );
            if ($include_basic) {
                $new_employee_stub['earnings'] = [$basic_earnings];
                $new_employee_stub['earnings_overpaid'] = $this->overwrite_value($new_employee_stub['earnings_overpaid'],
                    array(
                        'title' => \App\Adjustment::CONST_BASIC_PAY,
                        'amount' => 0
                    )
                );
                $new_employee_stub['earnings_underpaid'] = $this->overwrite_value($new_employee_stub['earnings_underpaid'],
                    array(
                        'title' => \App\Adjustment::CONST_BASIC_PAY,
                        'amount' => 0
                    )
                );

                $new_employee_stub['deductions'] = $employee_stub_copy['deductions'];
            }


            $new_employee_stub['loans'] = $include_loans
                ? $this->get_loans($employee_stub_copy)
                : [];
            $new_employee_stub['loans_refund'] = $include_loans
                ? $this->get_loans($employee_stub_copy)
                : [];
            // Log::debug($new_employee_stub['loans_refund']);
            $loans_refund = [];
            for($i=0; $i<sizeof($new_employee_stub['loans_refund']); $i++) {
                $temp = $new_employee_stub['loans_refund'][$i];
                $temp['amount'] = 0;
                array_push($loans_refund, $temp);
            }
            $new_employee_stub['loans_refund'] = $loans_refund;

            $new_employee_stub['reimbursements'] = $include_reimbursements
                ? $employee_stub_copy['reimbursements']
                : [];

            // add contributions_stub
            $adjustment_id_lookup = $all_adjustments->keyBy('id');

            // parang di ata kailangan
            $new_employee_stub['contributions_stub'] = [
                $this->get_pagibig_contribution($employee_stub_copy, $config_pagibig, $salary_with_step, $adjustment_lookup[\App\Adjustment::CONST_PAG_IBIG]),
                $this->get_gsis_contribution($employee_stub_copy, $config_gsis, $salary_with_step, $adjustment_lookup[\App\Adjustment::CONST_GSIS]),
                $this->get_philhealth_contribution($employee_stub_copy, $config_philhealth, $salary_with_step, $adjustment_lookup[\App\Adjustment::CONST_PHILHEALTH]),
                $this->get_from_stub($employee_stub_copy['contributions'],[
                    'title' => $adjustment_id_lookup[$napowa_id]->adjustment_name,
                    'id' => $adjustment_id_lookup[$napowa_id]->id,
                    'short_name' => $adjustment_id_lookup[$napowa_id]->short_name,
                    'amount' => (float) $adjustment_id_lookup[$napowa_id]->default_amount
                ])
            ];

            // $new_employee_stub['earnings_stub'] = [
            //     $this->get_from_stub($employee_stub_copy['earnings'],[
            //         'title' => $adjustment_lookup[$pera_allowance_id]->adjustment_name,'amount' => (float) $adjustment_lookup[$pera_allowance_id]->default_amount
            //     ])
            // ];
            if ($include_dtr) {
                $night_diff_multiplier = .10;
                $daily_rate = $salary_with_step / $request->input('days_in_month', 22);

                $daily_rate_late = $salary_with_step / 22;
                $per_hour_rate = $daily_rate_late / 8;
                $per_minute_rate = $per_hour_rate / 60;

                $dtr_deductions = $this->get_dtr_deductions($new_employee_stub->dtrs);

                $late_hours = floor($dtr_deductions->lates / 60);
                $late_minutes = $dtr_deductions->lates % 60;

                $undertime_hours = floor($dtr_deductions->undertimes / 60);
                $undertime_minutes = $dtr_deductions->undertimes % 60;

                $night_hours = floor($dtr_deductions->nights / 60);
                $night_minutes = $dtr_deductions->nights % 60;

                $new_employee_stub['deductions'] = array_merge(
                    $new_employee_stub['deductions'],
                    array(
                        array(
                            'title' => \App\Adjustment::CONST_LATE,
                            'id' => $adjustment_lookup[\App\Adjustment::CONST_LATE]->id,
                            'short_name' => $adjustment_lookup[\App\Adjustment::CONST_LATE]->short_name,
                            'amount' => round($late_hours * $per_hour_rate, 2) + round($late_minutes * $per_minute_rate, 2)
                        ),
                        array(
                            'title' => \App\Adjustment::CONST_UNDERTIME,
                            'id' => $adjustment_lookup[\App\Adjustment::CONST_UNDERTIME]->id,
                            'short_name' => $adjustment_lookup[\App\Adjustment::CONST_UNDERTIME]->short_name,
                            'amount' => round($undertime_hours * $per_hour_rate, 2) + round($undertime_minutes * $per_minute_rate, 2)
                        )
                    )
                );

                $new_employee_stub['earnings_overpaid'] = $this->overwrite_value($new_employee_stub['earnings_overpaid'],
                    array(
                        'title' => \App\Adjustment::CONST_BASIC_PAY,
                        'amount' => round($dtr_deductions->absences * $daily_rate, 2)
                    )
                );

                if (request('run_type') != self::RUN_TYPE_CONTRACTUAL) {
                    if ($dtr_deductions->absences > 0) {
                        $new_employee_stub['earnings_overpaid'] = $this->overwrite_value($new_employee_stub['earnings_overpaid'],
                            array(
                                'title' => \App\Adjustment::CONST_PERA_ALLOWANCE,
                                'id' => $adjustment_lookup[\App\Adjustment::CONST_PERA_ALLOWANCE]->id,
                                'short_name' => $adjustment_lookup[\App\Adjustment::CONST_PERA_ALLOWANCE]->short_name,
                                'amount' => round($dtr_deductions->absences * 90.91, 2)
                            )
                        );
                    }

                }

                # night differential
                if ($include_night_differential) {
                    $new_employee_stub['earnings'] = array_merge($new_employee_stub['earnings'], [
                        array(
                            'title' => \App\Adjustment::CONST_NIGHT_DIFF,
                            'amount' => round($night_hours * $per_hour_rate * $night_diff_multiplier, 2) + round($night_minutes * $night_diff_multiplier * $per_minute_rate, 2)
                        )
                    ]);
                }
            }


            // process all adjustments (earnings, deductions, statutory, taxes)
            foreach ($adjustments as $adjustment_id) {
                $adjustment = $adjustment_id_lookup[$adjustment_id];
                if ($adjustment->type == Adjustment::CONST_TYPE_EARNINGS) {
                    if ($adjustment->adjustment_name == Adjustment::CONST_YEAR_END_BONUS || $adjustment->adjustment_name == Adjustment::CONST_MID_YEAR_BONUS) {
                        // $value = $this->get_from_stub($employee_stub_copy['earnings'], [
                        //     'title' => $adjustment->adjustment_name,
                        //     'id' => $adjustment->id,
                        //     'short_name' => $adjustment->short_name,
                        //     'amount' => $salary_with_step
                        // ]);
                        $value = [
                            'title' => $adjustment->adjustment_name,
                            'id' => $adjustment->id,
                            'short_name' => $adjustment->short_name,
                            'amount' => $salary_with_step
                        ];
                    }
                    else if ($adjustment->adjustment_name == Adjustment::CONST_PREMIUM) {
                        $amount_for_premium = $basic_earnings['amount'];
                        $amount_for_premium -= $this->get_value_from_title($new_employee_stub['earnings_overpaid'], \App\Adjustment::CONST_BASIC_PAY);
                        $amount_for_premium -= $this->get_value_from_title($new_employee_stub['deductions'], \App\Adjustment::CONST_LATE);
                        $amount_for_premium -= $this->get_value_from_title($new_employee_stub['deductions'], \App\Adjustment::CONST_UNDERTIME);
                        $value = $this->get_from_stub($employee_stub_copy['earnings'], [
                            'title' => $adjustment->adjustment_name,
                            'id' => $adjustment->id,
                            'short_name' => $adjustment->short_name,
                            'amount' => $amount_for_premium * (float) $adjustment->default_amount / 100
                        ]);
                    }
                    else {
                        $value = $this->get_from_stub($employee_stub_copy['earnings'], [
                            'title' => $adjustment->adjustment_name,
                            'id' => $adjustment->id,
                            'short_name' => $adjustment->short_name,
                            'amount' => (float) $adjustment->default_amount
                        ]);
                    }
                    $new_employee_stub['earnings'] = array_merge($new_employee_stub['earnings'], [$value]);
                    $new_employee_stub['earnings_overpaid'] = array_merge($new_employee_stub['earnings_overpaid'], [
                        array(
                            'title' => $adjustment->adjustment_name,
                            'id' => $adjustment->id,
                            'short_name' => $adjustment->short_name,
                            'amount' => 0
                        )
                    ]);
                    $new_employee_stub['earnings_underpaid'] = array_merge($new_employee_stub['earnings_underpaid'], [
                        array(
                            'title' => $adjustment->adjustment_name,
                            'id' => $adjustment->id,
                            'short_name' => $adjustment->short_name,
                            'amount' => 0
                        )
                    ]);
                }
                else if ($adjustment->type == Adjustment::CONST_TYPE_DEDUCTIONS) {
                    $value = $this->get_from_stub($employee_stub_copy['deductions'], [
                        'title' => $adjustment->adjustment_name,
                        'id' => $adjustment->id,
                        'short_name' => $adjustment->short_name,
                        'amount' => (float) $adjustment->default_amount
                    ]);
                    $new_employee_stub['deductions'] = array_merge($new_employee_stub['deductions'], [$value]);
                }
                else if ($adjustment->type == Adjustment::CONST_TYPE_TAX) {
                    $new_employee_stub['taxes'] = array_merge($new_employee_stub['taxes'], [
                        [
                            'title' => $adjustment->adjustment_name,
                            'id' => $adjustment->id,
                            'short_name' => $adjustment->short_name,
                            'amount' => 0
                        ] // TODO: This is computed in the frontend
                    ]);
                }
                else if ($adjustment->type == Adjustment::CONST_TYPE_STATUTORY) {
                    if ($adjustment->adjustment_name == Adjustment::CONST_PAG_IBIG) {
                        $value = $this->get_pagibig_contribution($employee_stub_copy, $config_pagibig, $salary_with_step, $adjustment);
                    }
                    else if ($adjustment->adjustment_name == Adjustment::CONST_GSIS) {
                        $value = $this->get_gsis_contribution($employee_stub_copy, $config_gsis, $salary_with_step, $adjustment);
                    }
                    else if ($adjustment->adjustment_name == Adjustment::CONST_PHILHEALTH) {
                        $value = $this->get_philhealth_contribution($employee_stub_copy, $config_philhealth, $salary_with_step, $adjustment);
                    }
                    else {
                        $value = [
                            'title' => $adjustment->adjustment_name,
                            'id' => $adjustment->id,
                            'short_name' => $adjustment->short_name,
                            'amount' => (float) $adjustment->default_amount
                        ];
                        $value = $this->get_from_stub($employee_stub_copy['contributions'], $value);
                    }
                    $new_employee_stub['contributions'] = array_merge($new_employee_stub['contributions'], [$value]);
                }
            }

            if ($include_overtime) {
                $daily_rate = $salary_with_step / $request->input('days_in_month', 22);
                $hourly_rate = $daily_rate / 8;
                // process overtime and deduct if already used
                $total_amount = 0;
                foreach ($new_employee_stub->overtime_requests as &$overtime_request) {
                    //Log::debug($overtime_request);
                    $used = \DB::table('overtime_uses')
                        ->where('overtime_request_id','=',$overtime_request->id)
                        ->sum('duration_in_minutes');
                    if ($used != null) {
                        $overtime_request->duration_in_minutes = $overtime_request->duration_in_minutes - $used;
                    }
                    $overtime_date = Carbon::createFromFormat('Y-m-d', $overtime_request->dtr_date)->format('Y-m-d');
                    $time_data = $time_data_lookup[$overtime_date];
                    $overtime_request->multiplier = 1.25;

                    $overtime_request->date_type = 'Regular';
                    $is_weekend = Carbon::createFromFormat('Y-m-d', $overtime_request->dtr_date)->isWeekend();
                    if ($time_data) {
                        $overtime_request->multiplier = $time_data->multiplier;
                        $overtime_request->date_type = $time_data->time_data_name;
                    }
                    else if ($is_weekend) {
                        $overtime_request->multiplier = 1.50;
                        $overtime_request->date_type = 'Weekend';
                    }
                    $overtime_request->amount = round($overtime_request->duration_in_minutes / 60 * $hourly_rate * $overtime_request->multiplier,2);
                    $total_amount = $total_amount + $overtime_request->amount;
                }
                unset ($overtime_request);

                $new_employee_stub['earnings'] = array_merge(
                    $new_employee_stub['earnings'],
                    array(
                        array(
                            'title' => \App\Adjustment::CONST_OVERTIME,
                            'id' => $adjustment_lookup[\App\Adjustment::CONST_OVERTIME]->id,
                            'short_name' => $adjustment_lookup[\App\Adjustment::CONST_OVERTIME]->short_name,
                            'amount' => round($total_amount, 2)
                        )
                    )
                );
                $new_employee_stub['earnings_overpaid'] = array_merge(
                    $new_employee_stub['earnings_overpaid'],
                    array(
                        array(
                            'title' => \App\Adjustment::CONST_OVERTIME,
                            'id' => $adjustment_lookup[\App\Adjustment::CONST_OVERTIME]->id,
                            'short_name' => $adjustment_lookup[\App\Adjustment::CONST_OVERTIME]->short_name,
                            'amount' => 0
                        )
                    )
                );
                $new_employee_stub['earnings_underpaid'] = array_merge(
                    $new_employee_stub['earnings_underpaid'],
                    array(
                        array(
                            'title' => \App\Adjustment::CONST_OVERTIME,
                            'id' => $adjustment_lookup[\App\Adjustment::CONST_OVERTIME]->id,
                            'short_name' => $adjustment_lookup[\App\Adjustment::CONST_OVERTIME]->short_name,
                            'amount' => 0
                        )
                    )
                );
            }

            if ($new_employee_stub->dtrs->count() > 0) {
                //Log::debug($new_employee_stub->dtrs);
                $dtr_rejected = $new_employee_stub->dtrs->contains(function ($dtr) {
                    return $dtr->status === 4;
                });
                $dtr_approved = $new_employee_stub->dtrs->contains(function ($dtr) {
                    return $dtr->status === 3;
                });
                $new_employee_stub['dtr_status'] = $dtr_rejected ? -1 : ($dtr_approved ? 1 : 0);
            } else {
                $new_employee_stub['dtr_status'] = 0;
            }

            // add all employee_payroll_logs for the current year
            $payroll_logs = \App\PayrollEmployeeLog::where('year', Carbon::parse(request('payroll_date'))->format('Y'))
            ->where('employee_id', '=', $employee_stub->employment_type->employee_id)
            ->whereIn('type_of', [
                $this::LOG_TYPE_EARNINGS,
                $this::LOG_TYPE_CONTRIBUTIONS,
                $this::LOG_TYPE_DEDUCTIONS,
                $this::LOG_TYPE_TAXES,
                $this::LOG_TYPE_EARNINGS_OVERPAID,
                $this::LOG_TYPE_CONTRIBUTIONS_ARREAR,
                $this::LOG_TYPE_EARNINGS_UNDERPAID,
                $this::LOG_TYPE_CONTRIBUTIONS_REFUND,
                ])
                ->get();
            $new_employee_stub['logs'] = $payroll_logs;

            unset($new_employee_stub['latest_pagibig_request']);
            unset($new_employee_stub['loan_requests']);
            unset($new_employee_stub['salary']);
            unset($new_employee_stub['salary_grade']);
            unset($new_employee_stub['updated_at']);
        }

        return response()->json($response);
    }

    private function addToArray($paymentCat, $title, $amount, $new_employee_stub)
    {
        return array_merge(
            $new_employee_stub[$paymentCat],
            array(
                array(
                    'title' => $title,
                    'amount' => $amount
                ),
            )
        );
    }

    private function getClosest($search)
    {
        $closest = null;
        $index = 0;
        $arr = [
            0,
            .042, .083, .125, .167, .208, .250, .292, .333, .375, .417,
            .453, .500, .542, .583, .625, .667, .708, .750, .792, .833,
            .875, .917, .958, 1.000, 1.042, 1.083, 1.125, 1.167, 1.208, 1.250
        ];
        foreach ($arr as $key => $item) {
            if ($closest === null || abs($search - $closest) > abs($item - $search)) {
                $closest = $item;
                $index = $key;
            }
        }
        return $index;
    }

    private function deduction($amt, $dtr)
    {
        $amt = $amt;
        $totalDeduction = 0;
        $totalAbsence = 0;
        $dtrs = isset($dtr->dtr_submitted) ? $dtr->dtr_submitted : [];
        foreach ($dtrs as $dtr) {
            $daysAbsent = $this->getClosest(abs($dtr->undeductedSL));
            $totalAbsence += $daysAbsent;
            $totalDeduction += ($daysAbsent * $amt);
        }
        return [number_format($totalDeduction, 2, '.', ''), $totalAbsence];
    }

    public function get_earnings($salary, $payroll_type = self::PAYROLL_TYPE_MONTHLY , $month_work_days = 22)
    {
        $result = array(
            "title" => \App\Adjustment::CONST_BASIC_PAY,
            "amount" =>  $salary,
            "id" => -1
        );
        if ($payroll_type === self::PAYROLL_TYPE_SEMI_MONTHLY) {
            $result['amount'] = $result['amount'] / 2;
        } else if ($payroll_type === self::PAYROLL_TYPE_DAILY) {
            $start_date = Carbon::createFromFormat('Y-m-d', request('payroll_period_start'));
            $end_date = Carbon::createFromFormat('Y-m-d', request('payroll_period_end'));
            $workingDays = $start_date->diffInDaysFiltered(function (Carbon $date) {
                return !$date->isWeekend();
            }, $end_date);
            $result['amount'] = round($result['amount'] / $month_work_days * $workingDays,  2);
        }
        return $result;
    }

    public function get_loans($employee_stub)
    {
        $loans = $employee_stub['loans'] ?? [];

        foreach ($employee_stub['loan_requests'] as $loan_request) {
            $loan_entry_index = array_search($loan_request['id'], array_column($loans, 'loan_id'));
            if ($loan_entry_index !== false) {
                if ($employee_stub->updated_at <= $loan_request->updated_at) {
                    $loans[$loan_entry_index]['category'] = $loan_request->category;
                    $loans[$loan_entry_index]['amount'] = round(($loan_request->loan_amount / $loan_request->ammortization_number), 2);
                }
            } else {
                array_push($loans, array(
                    "title" => $loan_request->loan_name,
                    "amount" => round(($loan_request->loan_amount / $loan_request->ammortization_number), 2),
                    "loan_id" => $loan_request->id,
                    "category" => $loan_request->category
                ));
            }
        }
        return $loans;
    }

    public function get_pagibig_contribution($employee_stub, $config_pagibig, $salary, $adjustment)
    {
        $config_pagibig_contribution = (object) array();
        foreach ($config_pagibig as $row => $value) {
            $min = abs($value->maximum_range);
            $max = abs($value->minimum_range);
            if ($min >= $salary && $max <= $salary) {
                $config_pagibig_contribution = $value;
                break;
            }
        }

        if ($config_pagibig_contribution->government_share < 100) {
            $company_share = round($config_pagibig_contribution->government_share / 100 * $salary, 2);
        }
        else {
            $company_share = $config_pagibig_contribution->government_share;
        }

        $contributions = $employee_stub['contributions'] ?? [];
        $pagibig_entry_index = array_search(Adjustment::CONST_PAG_IBIG, array_column($contributions, 'title'));

        $approved_request = $employee_stub['latest_pagibig_request'];

        if (
            $pagibig_entry_index !== false &&
            (!$config_pagibig_contribution || $employee_stub['updated_at'] > $config_pagibig_contribution->updated_at) &&
            (!$approved_request || $employee_stub['updated_at'] > $approved_request->updated_at)
        ) {
            return array(
                "title" => $contributions[$pagibig_entry_index]['title'],
                "amount" => $contributions[$pagibig_entry_index]['amount'],
                'id' => $adjustment->id,
                'short_name' => $adjustment->short_name,
                "company_share" => $company_share,
            );
        } else if (
            $approved_request &&
            (!$config_pagibig_contribution || $approved_request->updated_at > $config_pagibig_contribution->updated_at)
        ) {
            return array(
                "title" => Adjustment::CONST_PAG_IBIG,
                "amount" => $approved_request['amount'],
                'id' => $adjustment->id,
                'short_name' => $adjustment->short_name,
                "company_share" => $company_share
            );
        } else {
            return array(
                "title" => Adjustment::CONST_PAG_IBIG,
                'id' => $adjustment->id,
                'short_name' => $adjustment->short_name,
                "amount" => !$config_pagibig_contribution ? 0 : (!$config_pagibig_contribution->is_percentage ? $config_pagibig_contribution->personal_share
                    : round($config_pagibig_contribution->personal_share * $salary, 2)),
                "company_share" => $company_share
            );
        }
    }

    public function get_gsis_contribution($employee_stub, $config_gsis, $salary, $adjustment)
    {
        $contributions = $employee_stub['contributions'] ?? [];
        $gsis_entry_index = array_search(Adjustment::CONST_GSIS, array_column($contributions, 'title'));

        $company_share = round($config_gsis->government_share * $salary, 2);
        $gsis_ecc = round($config_gsis->ecc, 2);
        if (
            false && // do not apply stub at all
            $gsis_entry_index !== false &&
            (!$config_gsis || $employee_stub['updated_at'] > $config_gsis->updated_at)
        ) {
            return array(
                "title" => $contributions[$gsis_entry_index]['title'],
                "amount" => $contributions[$gsis_entry_index]['amount'],
                'id' => $adjustment->id,
                'short_name' => $adjustment->short_name,
                "company_share" => $company_share,
                "gsis_ecc" => $gsis_ecc
            );
        } else {
            return array(
                "title" => Adjustment::CONST_GSIS,
                'id' => $adjustment->id,
                'short_name' => $adjustment->short_name,
                "amount" => !$config_gsis ? 0 : round($config_gsis->personal_share * $salary, 2),
                "company_share" => $company_share,
                "gsis_ecc" => $gsis_ecc
            );
        }
    }

    public function get_philhealth_contribution($employee_stub, $config_philhealth, $salary, $adjustment)
    {
        $config_philhealth_contribution = (object) array();
        foreach ($config_philhealth as $row => $value) {
            $max = abs($value->maximum_range);
            $min = abs($value->minimum_range);
            if ($max >= $salary && $min <= $salary) {
                $config_philhealth_contribution = $value;
                break;
            }
        }
        if ($config_philhealth_contribution->is_max) {
            $company_share = $config_philhealth_contribution->personal_share[0];
        }
        else {
            $company_share = round($config_philhealth_contribution->percentage * $salary / 100 / 2, 2, PHP_ROUND_HALF_DOWN);
        }


        $contributions = $employee_stub['contributions'] ?? [];
        $philhealth_entry_index = array_search(Adjustment::CONST_PHILHEALTH, array_column($contributions, 'title'));
        if (
            false && // do not apply stub at all
            $philhealth_entry_index !== false &&
            (!$config_philhealth_contribution || $employee_stub['updated_at'] > $config_philhealth_contribution->updated_at)
        ) {
            return array(
                "title" => $contributions[$philhealth_entry_index]['title'],
                'id' => $adjustment->id,
                'short_name' => $adjustment->short_name,
                "amount" => $contributions[$philhealth_entry_index]['amount'],
                "company_share" => $company_share
            );
        } else {
            if ($config_philhealth_contribution->is_max) {
                $amount = $config_philhealth_contribution->personal_share[0];
            }
            else {
                $amount = !$config_philhealth_contribution ? 0 : round($config_philhealth_contribution->percentage * $salary / 100 / 2, 2, PHP_ROUND_HALF_DOWN);
                $company_share = ($config_philhealth_contribution->percentage * $salary / 100) - $amount;
            }
            return array(
                "title" => Adjustment::CONST_PHILHEALTH,
                'id' => $adjustment->id,
                'short_name' => $adjustment->short_name,
                "amount" => $amount,
                "company_share" => $company_share
            );
        }
    }

    public function get_statutory_config_data($statutory)
    {
        switch ($statutory) {
            case 'gsis':
                return \DB::table('gsis')->where('status', 1)->first();
            case 'pagibig':
                return \DB::table('pagibig')->get();
            case 'philhealth':
                return \App\Philhealth::all();
            case 'tax':
                return \DB::table('tax')
                    ->where('isActive', '<=', 1)
                    // class serves as day of activation
                    // *
                    // class should will be active once it
                    // surpasses the current date
                    // tables whose class/date of activation are
                    // surpassed by todays date
                    // e.g. tax table has a class of january 15
                    // today is january 19 - that tax is active
                    //
                    // tax table has a class of january 19
                    // today is january 18 - this tax is not active
                    //
                    // tax tables have a class of january 18 and 19
                    // today is january 20 - the active tax is
                    // january 19 - what we consider as the
                    // latest active tax
                    ->where('class', '<=', Carbon::now())
                    // class descending to get the latest
                    // date possible to can be the active
                    // tax table
                    ->orderBy('class', 'desc')
                    ->get();
            default:
                return null;
        }
    }

    private function get_total($data = [])
    {
        $sum = (float) 0;
        foreach ($data as $datum) {
            $value = (object) $datum;
            $sum += (float) $value->amount ?? 0;
        }
        return $sum;
    }

    private function get_dtr_deductions($dtrs)
    {
        $adjustments = array(
            'undertimes' => 0,
            'lates' => 0,
            'absences' => 0,
            'nights' => 0
        );
        foreach ($dtrs as $dtr) {
            $dtr = (object) $dtr;
            $adjustments['absences'] += $dtr->absence_for_payment_deduction;
            $adjustments['lates'] += $dtr->late_for_payment_deduction;
            $adjustments['undertimes'] += $dtr->undertime_minutes;
            $adjustments['nights'] += $dtr->night_differential_minutes;
        }
        return (object) $adjustments;
    }

    public function get_payroll_years(Request $request)
    {
        $payroll_years = \App\PayrollEmployeeLog::distinct()->orderBy('year', 'asc')->get(['year'])->pluck('year');
        $year_now = Carbon::now()->format('Y');
        if (!$payroll_years->contains($year_now)) {
            $payroll_years->push($year_now);
        }
        return $payroll_years;
    }

    public function get_completed_payruns_ss(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $employee_id = $this->me->employee_details->id;

        $query = \App\Payrun::whereHas('employee_logs', function ($q) use ($employee_id) {
            $q->where('employee_id', $employee_id);
        })
            ->where('status', 2);

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function get_from_stub($arr, $value){
        $key = 'title';
        for($i=0; $i<sizeof($arr); $i++) {
            if ($arr[$i][$key] == $value[$key]) {
                return $arr[$i];
            }
        }
        // not found
        return $value;
    }

    public function get_value_from_title($arr, $title) {
        for ($i=0; $i<sizeof($arr); $i++) {
            if($arr[$i]['title'] == $title) {
                return $arr[$i]['amount'];
            }
        }
        return 0;
    }

    public function overwrite_value($arr, $value) {
        $key = 'title';
        $found = false;
        for($i=0; $i<sizeof($arr); $i++) {
            if ($arr[$i][$key] == $value[$key]) {
                $arr[$i]['amount'] = $value['amount'];
                $found = true;
            }
        }
        if ($found) {
            return $arr;
        }
        else {
            return array_merge($arr, [$value]);
        }
    }

    public function get_time_data_lookup($start, $end) {
        $current_start = Carbon::createFromFormat('Y-m-d', $start);
        $end = Carbon::createFromFormat('Y-m-d', $end);
        $end->addDays(1);
        $keys = [];

        while ($current_start->toDateString() != $end->toDateString()) {
            $current_end = Carbon::createFromFormat('Y-m-d', $current_start->format('Y-m-d'))->addDay();

            $keys[$current_start->format('Y-m-d')] =
                Holiday::get_time_data_if_holiday($current_start->format('Y-m-d'));

            $current_start = $current_end;
        }
        return $keys;

    }
}
