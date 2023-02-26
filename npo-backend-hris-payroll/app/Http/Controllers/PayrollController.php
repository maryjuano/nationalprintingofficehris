<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Payroll;
use App\Attendance;
use App\EmployeeStubList;

use Log;

class PayrollController extends Controller
{

    public function getMyTax(Request $request)
    {
        $body = json_decode($request->getContent(), true);
        $monthlyRate = $body["monthlyRate"];
        $payroll = new Payroll();

        return response()->json($payroll->calculateTax($monthlyRate))->setStatusCode(200);
    }

    public function getMyGSIS(Request $request)
    {
        $body = json_decode($request->getContent(), true);
        $monthlyRate = $body["monthlyRate"];
        $payroll = new Payroll();
        return response()->json($payroll->calculateGSIS($monthlyRate))->setStatusCode(200);
    }

    public function getMyPhilhealth(Request $request)
    {
        $body = json_decode($request->getContent(), true);
        $monthlyRate = $body["monthlyRate"];
        $payroll = new Payroll();
        return response()->json($payroll->calculatePhilHealth($monthlyRate))->setStatusCode(200);
    }

    public function compute_pay($request, $payrun_id, $is_new, $to_be_save)
    {
        if (!$is_new) {
            $query = \App\Payroll::where('payroll_run_id', $payrun_id)->get();
            foreach ($query as $row) {
                $row->delete();
            }
        }

        $response['payroll_run_id'] = $payrun_id;
        $startDate = Carbon::parse($request["payroll_period_start"])->format('Y-m-d H:i:s');
        $endDate =   Carbon::parse($request["payroll_period_end"])->format('Y-m-d H:i:s');
        $daysInMonth = $request["days_in_month"];
        /** value fix for now 22. As per sir aniel needed in napc but should be fix and adjust on FE **/
        $all_employee_ids_list = \App\Employee::where('status', 1)
            ->has('employment_and_compensation')
            ->pluck('id');
        $employeeIds = $to_be_save ? $request["employee_ids"] : $all_employee_ids_list;


        $payrollHelper = new Payroll();
        $multipliers = $payrollHelper->getTimeDataMultipliers();
        $nightDiffSettings["start"] = "18:00:00";
        $nightDiffSettings["end"] = "06:00:00";
        $nightDiffSettings["multiplier"] = $multipliers["nightDiffMultiplier"];
        $restDaySettings["multiplier"] = $multipliers["restDayMultiplier"];
        $overtimeSettings["multiplier"] = $multipliers["regularOvertimeMultiplier"];
        $overtimeSettings["multiplierRest"] = $multipliers["restOvertimeMultiplier"];
        $tempBody["restDaySettings"] = $restDaySettings;
        $tempBody["overtimeSettings"] = $overtimeSettings;

        $att = new Attendance();
        $payroll = array();
        $dtr_needed_work_sched_id = \App\WorkSchedule::whereIn('time_option', [1, 2])
            ->pluck('id')
            ->toArray();
        $dtr_needed_flexi = \App\WorkSchedule::whereIn('time_option', [4])
            ->pluck('id')
            ->toArray();

        $employee_query = \DB::table('employment_and_compensation')
            ->whereIn('employee_id', $employeeIds)->get();

        $attendance_query = \DB::table('dtr_submitted')
            ->whereBetween('startDate', [$startDate, $endDate])
            ->whereBetween('endDate', [$startDate, $endDate])
            ->whereIn('employeeId', $employeeIds)
            ->orderBy('startDate')
            ->get();

        foreach ($employeeIds as $employeeId) {
            $queryEmployees = $employee_query
                ->where('employee_id', '=', $employeeId)
                ->first();

            $queryAttendance = $attendance_query
                ->where('employeeId', '=', $employeeId)
                ->pluck('dtr');

            $attendance = array(
                'dtrs' => [],
                'schedule' => [],
            );

            foreach ($queryAttendance as $entry) {
                $decoded = json_decode($entry, true);
                $attendance['dtrs'] = array_merge($attendance['dtrs'], $decoded['dtrs']);
                if (empty($attendance['schedule'])) {
                    $attendance['schedule'] = array_merge($attendance['schedule'], $decoded['schedule']);
                }
            }

            if (in_array($queryEmployees->work_schedule_id, $dtr_needed_work_sched_id) && $queryAttendance) {
                $dtrs = $attendance["dtrs"];
                $holidays = \App\Attendance::getHolidays($startDate, $endDate);
                $schedule = $attendance["schedule"];
                $dailyRate = round($payrollHelper->getMonthlyRate($employeeId, $startDate) / $daysInMonth, 2);
                $perSecondRate = $dailyRate / 8 / 60 / 60;
                $newDtrs = array();
                $lateDuration = 0;
                $undertimeDuration = 0;
                $absenceDuration = 0;
                $restDayPay = 0;
                $holidayPay = 0;
                $nightDiffPay = 0;
                $otPay = 0;

                $queryEmployeesForTimeOption = \DB::table('employees')
                    ->leftJoin('personal_information', 'employees.id', '=', 'personal_information.employee_id')
                    ->leftJoin('employment_and_compensation', 'employees.id', '=', 'employment_and_compensation.employee_id')
                    ->leftJoin('work_schedules', 'employment_and_compensation.work_schedule_id', '=', 'work_schedules.id')
                    ->leftJoin('fixed_daily_hours', 'work_schedules.id', '=', 'fixed_daily_hours.work_schedule_id')
                    ->leftJoin('fixed_daily_times', 'work_schedules.id', '=', 'fixed_daily_times.work_schedule_id')
                    ->select(
                        'work_schedules.flexible_weekly_hours',
                        'work_schedules.time_option',
                        'employees.status',
                        'personal_information.first_name',
                        'personal_information.middle_name',
                        'personal_information.last_name',
                        'personal_information.name_extension',
                        'fixed_daily_hours.daily_hours',
                        'fixed_daily_times.start_times',
                        'fixed_daily_times.end_times',
                        'fixed_daily_times.grace_periods',
                        'employment_and_compensation.work_schedule_id',
                        'employment_and_compensation.employee_id'
                    )
                    ->where('employees.id', '=', $employeeId)
                    ->first();

                for ($dtrCount = 0; $dtrCount < count($dtrs); $dtrCount++) {
                    $lates = \App\Attendance::getLatesInMinutes($dtrs[$dtrCount], $schedule, $holidays, $queryEmployeesForTimeOption->time_option);
                    $undertimes = \App\Attendance::getUndertimesInMinutes($dtrs[$dtrCount], $schedule, $holidays, $queryEmployeesForTimeOption->time_option);
                    $absences = \App\Attendance::getAbsenceCount($dtrs[$dtrCount], $schedule, $holidays, $queryEmployeesForTimeOption->time_option);
                    $additionalPay = $att->additionalCounter(
                        $dtrs[$dtrCount],
                        $schedule,
                        $holidays,
                        null,
                        $dtrs[$dtrCount]["overtime"],
                        $nightDiffSettings,
                        $dailyRate,
                        $tempBody
                    );

                    $response["date"] = $dtrs[$dtrCount]["date"];
                    $response["basicPay"]["late"] = gmdate('H:i:s', $lates);
                    $response["basicPay"]["lateDeduction"] = round($lates * $perSecondRate, 2);
                    $response["basicPay"]["undertime"] = gmdate('H:i:s', $undertimes);
                    $response["basicPay"]["undertimeDeduction"] = round($undertimes * $perSecondRate, 2);
                    $response["basicPay"]["absence"] = $absences;
                    $response["basicPay"]["absenceDeduction"] = round($absences * $dailyRate, 2);
                    $response["additionalPay"]["holidayDuration"] = $additionalPay["holidayDuration"];
                    $response["additionalPay"]["holidayPay"] = $additionalPay["holidayPay"];
                    $response["additionalPay"]["nightDiffDuration"] = $additionalPay["nightDiffDuration"];
                    $response["additionalPay"]["nightDiffPay"] = round($additionalPay["nightDiffPay"], 2);
                    $response["additionalPay"]["otDuration"] = $additionalPay["otDuration"];
                    $response["additionalPay"]["otPay"] = round($additionalPay["otPay"], 2);
                    $response["additionalPay"]["restDayDuration"] = $additionalPay["restDayDuration"];
                    $response["additionalPay"]["restDayPay"] = $additionalPay["restDayPay"];

                    $nightDiffPay = $nightDiffPay + $additionalPay["nightDiffPay"];
                    $holidayPay += $additionalPay["holidayPay"];
                    $otPay = $otPay + $additionalPay["otPay"];
                    $restDayPay = $restDayPay + $additionalPay["restDayPay"];

                    $lateDuration = $lateDuration + $lates;
                    $undertimeDuration = $undertimeDuration + $undertimes;
                    $absenceDuration = $absenceDuration + $absences;

                    $dayOfWeek = date("l", strtotime($dtrs[$dtrCount]["date"]));
                    $dtrDayOfWeek = $att->getDayIntOfWeek($dayOfWeek);

                    if ($schedule[$dtrDayOfWeek]["type"] == 0) {
                        $response["basicPay"]["basicPay"] = 0;
                    } else {
                        $response["basicPay"]["basicPay"] = round($dailyRate - round($lates * $perSecondRate, 2)
                            - round($undertimes * $perSecondRate, 2) - round($absences * $dailyRate, 2), 2);

                        if (count($holidays) > 0) {
                            $response["basicPay"]["basicPay"] = round($dailyRate - round($lates * $perSecondRate, 2)
                                - round($undertimes * $perSecondRate, 2) - round($absences * $dailyRate, 2), 2);
                            foreach ($holidays as $holiday) {
                                if ($dtrs[$dtrCount]["date"] == $holiday["holidayDate"]) {
                                    $response["basicPay"]["basicPay"] = $dailyRate;
                                }
                            }
                        }
                    }
                    array_push($newDtrs, $response);
                }

                $lateCount = gmdate('H:i:s', $lateDuration);
                $undertimeCount = gmdate('H:i:s', $undertimeDuration);
                $responses["employee"] = $employeeId;
                $responses["monthlyRate"] = $payrollHelper->getMonthlyRate($employeeId, $startDate);
                $responses["dailyRate"] = $dailyRate;
                $responses["dtrs"] = $newDtrs;
                $responses["additionalPay"]["holiday"] = round($holidayPay, 2);
                $responses["additionalPay"]["overtime"] = round($otPay, 2);
                $responses["additionalPay"]["rest"] = round($restDayPay, 2);
                $responses["additionalPay"]["night"] = round($nightDiffPay, 2);
                $responses["dtrDeduction"]["late"] = round($lateDuration * $perSecondRate, 2);
                $responses["dtrDeduction"]["undertime"] = round($undertimeDuration * $perSecondRate, 2);
                $responses["dtrDeduction"]["absence"] = round($absenceDuration * $dailyRate);
                $responses["net_taxable_pay"] = $responses["monthlyRate"] - $responses["dtrDeduction"]["late"]
                    - $responses["dtrDeduction"]["undertime"] - $responses["dtrDeduction"]["absence"]
                    + $responses["additionalPay"]["holiday"] + $responses["additionalPay"]["overtime"]
                    + $responses["additionalPay"]["rest"] + $responses["additionalPay"]["night"];

                if ($to_be_save) {
                    $payroll_table = new \App\Payroll();
                    $payroll_table->employeeId = $employeeId;
                    $payroll_table->startDate = $startDate;
                    $payroll_table->endDate = $endDate;
                    $payroll_table->pay_structure = json_encode($responses);
                    $payroll_table->status = 0;
                    $payroll_table->payroll_run_id = $payrun_id;
                    $payroll_table->save();
                }

                array_push($payroll, $responses);
            } else if (in_array($queryEmployees->work_schedule_id, $dtr_needed_flexi) && $queryAttendance) {
                $dtrs = $attendance["dtrs"];
                $holidays = \App\Attendance::getHolidays($startDate, $endDate);
                $schedule = $attendance["schedule"];
                $dailyRate = round($payrollHelper->getMonthlyRate($employeeId, $startDate) / $daysInMonth, 2);
                $perSecondRate = $dailyRate / 8 / 60 / 60;
                $newDtrs = array();
                $lateDuration = 0;
                $undertimeDuration = 0;
                $absenceDuration = 0;
                $restDayPay = 0;
                $holidayPay = 0;
                $nightDiffPay = 0;
                $otPay = 0;

                for ($dtrCount = 0; $dtrCount < count($dtrs); $dtrCount++) {
                    $lates = 0;
                    $undertimes = \App\Attendance::getUndertimesInMinutes($dtrs[$dtrCount], $schedule, $holidays, $queryEmployeesForTimeOption->time_option);
                    $absences = \App\Attendance::getAbsenceCount($dtrs[$dtrCount], $schedule, $holidays, $queryEmployeesForTimeOption->time_option);
                    $additionalPay = null;
                    $response["date"] = $dtrs[$dtrCount]["date"];
                    $response["basicPay"]["late"] = gmdate('H:i:s', $lates);
                    $response["basicPay"]["lateDeduction"] = round($lates * $perSecondRate, 2);
                    $response["basicPay"]["undertime"] = gmdate('H:i:s', $undertimes);
                    $response["basicPay"]["undertimeDeduction"] = round($undertimes * $perSecondRate, 2);
                    $response["basicPay"]["absence"] = $absences;
                    $response["basicPay"]["absenceDeduction"] = round($absences * $dailyRate, 2);
                    $response["additionalPay"]["holidayDuration"] = $additionalPay["holidayDuration"];
                    $response["additionalPay"]["holidayPay"] = $additionalPay["holidayPay"];
                    $response["additionalPay"]["nightDiffDuration"] = $additionalPay["nightDiffDuration"];
                    $response["additionalPay"]["nightDiffPay"] = round($additionalPay["nightDiffPay"], 2);
                    $response["additionalPay"]["otDuration"] = $additionalPay["otDuration"];
                    $response["additionalPay"]["otPay"] = round($additionalPay["otPay"], 2);
                    $response["additionalPay"]["restDayDuration"] = $additionalPay["restDayDuration"];
                    $response["additionalPay"]["restDayPay"] = $additionalPay["restDayPay"];

                    $nightDiffPay = $nightDiffPay + $additionalPay["nightDiffPay"];
                    $holidayPay += $additionalPay["holidayPay"];
                    $otPay = $otPay + $additionalPay["otPay"];
                    $restDayPay = $restDayPay + $additionalPay["restDayPay"];
                    $lateDuration = $lateDuration + $lates;
                    $undertimeDuration = $undertimeDuration + $undertimes;
                    $absenceDuration = $absenceDuration + $absences;
                    $dayOfWeek = date("l", strtotime($dtrs[$dtrCount]["date"]));
                    $dtrDayOfWeek = $att->getDayIntOfWeek($dayOfWeek);

                    if ($schedule[$dtrDayOfWeek]["type"] == 0) {
                        $response["basicPay"]["basicPay"] = 0;
                    } else {
                        $response["basicPay"]["basicPay"] = round($dailyRate - round($lates * $perSecondRate, 2)
                            - round($undertimes * $perSecondRate, 2) - round($absences * $dailyRate, 2), 2);
                        if (count($holidays) > 0) {
                            $response["basicPay"]["basicPay"] = round($dailyRate - round($lates * $perSecondRate, 2)
                                - round($undertimes * $perSecondRate, 2) - round($absences * $dailyRate, 2), 2);
                            foreach ($holidays as $holiday) {
                                if ($dtrs[$dtrCount]["date"] == $holiday["holidayDate"]) {
                                    $response["basicPay"]["basicPay"] = $dailyRate;
                                }
                            }
                        }
                    }
                    array_push($newDtrs, $response);
                }

                $lateCount = gmdate('H:i:s', $lateDuration);
                $undertimeCount = gmdate('H:i:s', $undertimeDuration);

                $responses["employee"] = $employeeId;
                $responses["monthlyRate"] = $payrollHelper->getMonthlyRate($employeeId, $startDate);
                $responses["dailyRate"] = $dailyRate;
                $responses["dtrs"] = $newDtrs;
                $responses["additionalPay"]["holiday"] = round($holidayPay, 2);
                $responses["additionalPay"]["overtime"] = round($otPay, 2);
                $responses["additionalPay"]["rest"] = round($restDayPay, 2);
                $responses["additionalPay"]["night"] = round($nightDiffPay, 2);


                $responses["dtrDeduction"]["late"] = round($lateDuration * $perSecondRate, 2);
                $responses["dtrDeduction"]["undertime"] = round($undertimeDuration * $perSecondRate, 2);
                $responses["dtrDeduction"]["absence"] = round($absenceDuration * $dailyRate);

                $responses["net_taxable_pay"] = $responses["monthlyRate"] - $responses["dtrDeduction"]["late"]
                    - $responses["dtrDeduction"]["undertime"] - $responses["dtrDeduction"]["absence"]
                    + $responses["additionalPay"]["holiday"] + $responses["additionalPay"]["overtime"]
                    + $responses["additionalPay"]["rest"] + $responses["additionalPay"]["night"];

                if ($to_be_save) {
                    $payroll_table = new \App\Payroll();
                    $payroll_table->employeeId = $employeeId;
                    $payroll_table->startDate = $startDate;
                    $payroll_table->endDate = $endDate;
                    $payroll_table->pay_structure = json_encode($responses);
                    $payroll_table->status = 0;
                    $payroll_table->payroll_run_id = $payrun_id;
                    $payroll_table->save();
                }
                array_push($payroll, $responses);
            } else {
                $responses["employee"] = $employeeId;
                $responses["monthlyRate"] = $payrollHelper->getMonthlyRate($employeeId, $startDate);
                $responses["dailyRate"] = $responses["monthlyRate"] / $daysInMonth;
                $responses["dtrs"] = null;

                $responses["additionalPay"]["holiday"] = 0;
                $responses["additionalPay"]["overtime"] = 0;
                $responses["additionalPay"]["rest"] = 0;
                $responses["additionalPay"]["night"] = 0;

                $responses["dtrDeduction"]["late"] = 0;
                $responses["dtrDeduction"]["undertime"] = 0;
                $responses["dtrDeduction"]["absence"] = 0;

                $responses["net_taxable_pay"] = $responses["monthlyRate"] - $responses["dtrDeduction"]["late"]
                    - $responses["dtrDeduction"]["undertime"] - $responses["dtrDeduction"]["absence"]
                    + $responses["additionalPay"]["holiday"] + $responses["additionalPay"]["overtime"]
                    + $responses["additionalPay"]["rest"] + $responses["additionalPay"]["night"];

                if ($to_be_save) {
                    $payroll_table = new \App\Payroll();
                    $payroll_table->employeeId = $employeeId;
                    $payroll_table->startDate = $startDate;
                    $payroll_table->endDate = $endDate;
                    $payroll_table->pay_structure = json_encode($responses);
                    $payroll_table->status = 0;
                    $payroll_table->payroll_run_id = $payrun_id;
                    $payroll_table->save();
                }
                array_push($payroll, $responses);
            }
        }

        return $payroll;
    }

    public function exportPayrollRegistry()
    {
        $payrollHelper = new Payroll();
        $headers = [
            'Content-Type' => 'application/xlsx',
        ];
        $file = $payrollHelper->exportPayrollRegistry();
        return response()->download($file, 'registry.xlsx', $headers);
    }

    public function exportAlphaList($version, $startDate, $endDate, $employeeIds)
    {
        $employeeIds = json_decode($employeeIds, true);

        $payrollHelper = new Payroll();
        $headers = [
            'Content-Type' => 'application/xlsx',
        ];
        $file = $payrollHelper->exportAlphaList($version, $startDate, $endDate, $employeeIds);

        return response()->download($file);
    }

    public function generatePayslip($startDate, $endDate, $employeeId)
    {
        $payrollHelper = new Payroll();

        $file = $payrollHelper->generatePayslip($startDate, $endDate, $employeeId);
        return response()->download($file);
    }

    private function getPaystructure(Request $request, $ids)
    {
        $employees_stubs = \App\EmployeeStubList::with([
            'latest_pagibig_request',
            'loan_requests' => function ($q) {
                $q->where('status', 1)
                    ->whereRaw('
                    loan_requests.loan_amount > (
                        SELECT IFNULL(SUM(loan_payments.amount), 0)
                        FROM loan_payments
                        WHERE loan_payments.loan_request_id = loan_requests.id
                    )
                ');
            },
            'salary',
            'dtr_submitted' => function ($q) use ($request) {
                $q->where('status', '>=', 3);
                $q->whereBetween('startDate', [$request->input('deduction_start'), $request->input('deduction_end')]);
                $q->whereBetween('endDate', [$request->input('deduction_start'), $request->input('deduction_end')]);
            },
            'employment_type'
        ])->whereIn('employee_id', $ids)->get();
        $include_statutory = (in_array(4, $request->input('other_inclusion')));
        $include_adjustment =  (in_array(2, $request->input('other_inclusion')));
        $include_tax = (in_array(3, $request->input('other_inclusion')));
        $include_ot = (in_array(1, $request->input('other_inclusion')));
        $include_dtr = (in_array(0, $request->input('other_inclusion')));

        if ($include_tax) {
            $config_tax_table = $this->get_statutory_config_data('tax');
        }

        if ($include_statutory) {
            $config_philhealth = $this->get_statutory_config_data('philhealth');
            $config_gsis = $this->get_statutory_config_data('gsis');
            $config_pagibig = $this->get_statutory_config_data('pagibig');
        }

        foreach ($employees_stubs as $employee_stub) {
            $new_employee_stub = $employee_stub;
            $employee_is_temporary = $employee_stub->employment_type->employee_type_id === \App\EmployeeType::COS || $employee_stub->employment_type->employee_type_id === \App\EmployeeType::JOB_ORDER;
            $salary_with_step = $employee_is_temporary ?
                $employee_stub->employment_type->salary_rate :
                $employee_stub->salary->step[$employee_stub->step_increment ?? 0];

            $earnings = $this->get_earnings(
                $new_employee_stub,
                $salary_with_step,
                $request->input('payroll_type'),
                $request->input('days_in_month'),
                $include_adjustment,
                $employee_stub->employment_type->employee_type_id,
                $request->input('deduction_end')
            );
            $new_employee_stub['earnings'] = $earnings[0];
            $new_employee_stub['basic_pay'] = $new_employee_stub['earnings'][0]['amount'];
            $new_employee_stub['contribution'] = $include_statutory
                ? $this->get_contributions($new_employee_stub, $config_philhealth, $config_gsis, $config_pagibig, $salary_with_step)
                : [];
            $new_employee_stub['deductions'] = $include_adjustment
                ? $new_employee_stub['deductions'] ?? []
                : [];
            $new_employee_stub['reimbursement'] = $include_adjustment
                ? $new_employee_stub['reimbursement'] ?? []
                : [];
            $new_employee_stub['loans'] = $include_adjustment
                ? $this->get_loans($new_employee_stub)
                : [];
            $deduct = $this->deduction(90.91, $employee_stub);
            if ($include_dtr) {
                $daily_rate = round($salary_with_step / $request->input('days_in_month', 22), 2);
                $per_hour_rate = round($daily_rate / 8, 2);
                $per_minute_rate = round($per_hour_rate / 60, 2);
                $additional_deductions = $this->get_time_adjustments($new_employee_stub->dtr_submitted, $request->input('deduction_start'), $request->input('deduction_end'));

                $late_hours = round($additional_deductions->lates / 60, 0);
                $late_minutes = $additional_deductions->lates % 60;

                $undertime_hours = round($additional_deductions->undertimes / 60, 0);
                $undertime_minutes = $additional_deductions->undertimes % 60;

                $hasPera = $earnings[1]; // if pera is false commence with deduction

                $absenceDeduction = $hasPera === false ?
                    round($deduct[1] * $daily_rate, 2) : 0; //change 178 to a function that caluclates the actual deduction needed

                $new_employee_stub['deductions'] = array_merge(
                    $new_employee_stub['deductions'],
                    array(
                        array(
                            'title' => 'Late',
                            'amount' => round($late_hours * $per_hour_rate, 2) + round($late_minutes * $per_minute_rate, 2)
                        ),
                        array(
                            'title' => 'Undertime',
                            'amount' => round($undertime_hours * $per_hour_rate, 2) + round($undertime_minutes * $per_minute_rate, 2)
                        ),
                        array(
                            'title' => 'Absence',
                            'amount' =>  $absenceDeduction
                        )
                    )
                );
            }
            if ($hasPera) {
                $new_employee_stub['deductions'] = $this->addToArray(
                    'deductions',
                    'Opaid Allowance',
                    $deduct[0],
                    $new_employee_stub
                );
            }
            if ($include_ot) {
                $new_employee_stub['earnings'] = $this->addToArray(
                    'earnings',
                    'Overtime',
                    0,
                    $new_employee_stub
                );
            }

            $sum_of_earnings = $this->get_total($new_employee_stub['earnings']);
            $sum_of_contribution = $this->get_total($new_employee_stub['contribution']);
            $sum_of_deduction = $this->get_total($new_employee_stub['deductions']);
            $sum_of_loans = $this->get_total($new_employee_stub['loans']);
            $sum_of_reimbursement = $this->get_total($new_employee_stub['reimbursement']);

            $grosspay = round(($sum_of_earnings + $sum_of_reimbursement), 2);
            $tax = $include_tax
                ? $this->get_tax(
                    $config_tax_table,
                    $new_employee_stub['basic_pay'],
                    $sum_of_contribution,
                    $this->getNAPOWA($new_employee_stub['deductions']),
                    $employee_stub->employment_type->employee_type_id,
                    $request->input('deduction_end')
                )
                : null;
            $tax_amount = $tax['amount'] ?? 0;
            $total_deduction = round(($sum_of_contribution +  $sum_of_deduction +  $sum_of_loans + $tax_amount), 2);
            $netpay = round(($grosspay - $total_deduction), 2);

            $new_employee_stub['gross_pay'] = $grosspay;
            $new_employee_stub['net_pay'] = $netpay;
            $new_employee_stub['total_deduction'] = $total_deduction;
            $new_employee_stub['tax'] = $tax;
            $new_employee_stub['dtr_status'] = 1;
            unset($new_employee_stub['latest_pagibig_request']);
            unset($new_employee_stub['loan_requests']);
            unset($new_employee_stub['salary']);
            unset($new_employee_stub['salary_grade']);
            unset($new_employee_stub['step_increment']);
            unset($new_employee_stub['updated_at']);
        }
        return $employees_stubs;
    }
}
