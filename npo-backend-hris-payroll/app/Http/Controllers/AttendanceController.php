<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\SectionController;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use App\Attendance;
use App\Helpers\DayFractions;
use Clockwork\Support\Symfony\ClockworkListener;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AttendanceController extends Controller
{
    public function view_employees_attendance(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_employee_dtr']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'department_id' => 'required|exists:departments,id',
            'section_id' => 'required|exists:sections,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $holidays = \App\Attendance::getHolidays($startDate, $endDate);
        $days = \App\Attendance::getFormattedDtrDays($startDate, $endDate, $holidays);
        $employees = array();

        $currentDtrStartDate = Carbon::createFromFormat('Y-m-d', $startDate)->subDays(1)->format('Y-m-d');
        $currentDtrEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->addDays(1)->format('Y-m-d');
        $queryEmployees = \App\Employee::with([
            'biometrics' => function ($q) use ($currentDtrStartDate, $currentDtrEndDate) {
                $q->whereBetween('attendance', [$currentDtrStartDate . " 18:00:00", $currentDtrEndDate . " 23:59:59"]);
            },
            'overtime_requests' => function ($q) use ($currentDtrStartDate, $currentDtrEndDate) {
                $q->whereBetween('start_time', [$currentDtrStartDate . " 00:00:00", $currentDtrEndDate . " 23:59:59"]);
            }
        ])
            ->where([
                ['employment_and_compensation.department_id', $request->input('department_id')],
                ['employment_and_compensation.section_id', $request->input('section_id')]
            ])
            ->leftJoin('employment_and_compensation', 'employees.id', '=', 'employment_and_compensation.employee_id')
            ->leftJoin('work_schedules', 'employment_and_compensation.work_schedule_id', '=', 'work_schedules.id')
            ->leftJoin('fixed_daily_hours', 'work_schedules.id', '=', 'fixed_daily_hours.work_schedule_id')
            ->leftJoin('fixed_daily_times', 'work_schedules.id', '=', 'fixed_daily_times.work_schedule_id')
            ->leftJoin('employee_id_number', 'employees.id', 'employee_id_number.employee_id')
            ->select(
                'employees.id',
                'work_schedules.flexible_weekly_hours',
                'work_schedules.time_option',
                'employees.status',
                'fixed_daily_hours.daily_hours',
                'fixed_daily_times.start_times',
                'fixed_daily_times.end_times',
                'fixed_daily_times.grace_periods',
                'fixed_daily_times.end_times_is_next_day',
                'employment_and_compensation.work_schedule_id',
                'employment_and_compensation.employee_id',
                'employee_id_number.id_number'
            )->get();

        $breakTimesList = \App\BreakTime::where('type', 0)->get();
        $timeOffsList = \App\Attendance::getTimeOffs($queryEmployees->pluck('employee_id'), $startDate, $endDate);

        foreach ($queryEmployees as $employee) {
            $dtrSubmittedList = \App\DtrSubmit::with('dtrs')
                ->where('employee_id', $employee->employee_id)
                ->whereHas('dtrs', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('dtr_date', [$startDate, $endDate]);
                });

            array_push(
                $employees,
                self::getAttendanceEmployeeItem(
                    $employee,
                    $startDate,
                    $endDate,
                    $holidays,
                    $breakTimesList,
                    $dtrSubmittedList,
                    $timeOffsList
                )
            );
        }

        return response()->json(array(
            'holidays' => $holidays,
            'days' => $days,
            'employees' => $employees
        ));
    }

    public function view_employee_attendance(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_employee_dtr']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $holidays = \App\Attendance::getHolidays($startDate, $endDate);
        $days = \App\Attendance::getFormattedDtrDays($startDate, $endDate, $holidays);

        $currentDtrStartDate = Carbon::createFromFormat('Y-m-d', $startDate)->subDays(1)->format('Y-m-d');
        $currentDtrEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->addDays(1)->format('Y-m-d');
        $queryEmployee = \App\Employee::with([
            'biometrics' => function ($q) use ($currentDtrStartDate, $currentDtrEndDate) {
                $q->whereBetween('attendance', [$currentDtrStartDate . " 00:00:00", $currentDtrEndDate . " 23:59:59"]);
            },
            'overtime_requests' => function ($q) use ($currentDtrStartDate, $currentDtrEndDate) {
                $q->whereBetween('start_time', [$currentDtrStartDate . " 00:00:00", $currentDtrEndDate . " 23:59:59"]);
            }
        ])
            ->where('employees.id', $request->input('employee_id'))
            ->leftJoin('employment_and_compensation', 'employees.id', '=', 'employment_and_compensation.employee_id')
            ->leftJoin('work_schedules', 'employment_and_compensation.work_schedule_id', '=', 'work_schedules.id')
            ->leftJoin('fixed_daily_hours', 'work_schedules.id', '=', 'fixed_daily_hours.work_schedule_id')
            ->leftJoin('fixed_daily_times', 'work_schedules.id', '=', 'fixed_daily_times.work_schedule_id')
            ->leftJoin('employee_id_number', 'employees.id', 'employee_id_number.employee_id')
            ->select(
                'employees.id',
                'work_schedules.flexible_weekly_hours',
                'work_schedules.time_option',
                'employees.status',
                'fixed_daily_hours.daily_hours',
                'fixed_daily_times.start_times',
                'fixed_daily_times.end_times',
                'fixed_daily_times.grace_periods',
                'fixed_daily_times.end_times_is_next_day',
                'employment_and_compensation.work_schedule_id',
                'employment_and_compensation.employee_id',
                'employee_id_number.id_number'
            )->first();

        $breakTimesList = \App\BreakTime::where('type', 0)->get();
        $timeOffsList = \App\Attendance::getTimeOffs([$queryEmployee->id], $startDate, $endDate);

        $dtrSubmittedList = \App\DtrSubmit::with('dtrs')
            ->where('employee_id', $queryEmployee->id)
            ->whereHas('dtrs', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('dtr_date', [$startDate, $endDate]);
            });

        $employee = self::getAttendanceEmployeeItem(
            $queryEmployee,
            $startDate,
            $endDate,
            $holidays,
            $breakTimesList,
            $dtrSubmittedList,
            $timeOffsList
        );

        return response()->json(array(
            'holidays' => $holidays,
            'days' => $days,
            'employee' => $employee
        ));
    }

    private static function getAttendanceEmployeeItem($employee, $startDate, $endDate, $holidays, $breakTimesList, $dtrSubmittedList, $timeOffsList)
    {
        $employee_item = array();
        $employee_item['employee_id'] = $employee->employee_id;
        $employee_item['name'] = $employee->name;
        $employee_item['dtrs'] = collect();

        $employee_item['total_rendered_minutes'] = 0;
        $employee_item['total_overtime_minutes'] = 0;
        $employee_item['total_late_minutes'] = 0;
        $employee_item['total_undertime_minutes'] = 0;
        $employee_item['total_days_with_late'] = 0;
        $employee_item['total_days_with_undertime'] = 0;
        $employee_item['absences'] = 0;

        $breaks = $breakTimesList->firstWhere('work_schedule_id', $employee->work_schedule_id);
        $schedule = \App\Attendance::getSchedule($employee, $breaks);

        $current_dtr_date = Carbon::createFromFormat('Y-m-d', $startDate);
        $dtr_end_date = Carbon::createFromFormat('Y-m-d', $endDate);

        $submitted_dtr = $dtrSubmittedList->firstWhere('employee_id', $employee_item['employee_id']);
        $employee_item['dtr_status'] = $submitted_dtr === null ? 0 : $submitted_dtr->status;
        $employee_item['dtr_submit_id'] = $submitted_dtr === null ? null : $submitted_dtr->id;
        $dtrs = $submitted_dtr === null ? collect() : $submitted_dtr->dtrs;
        for ($current_dtr_date; $current_dtr_date->lessThanOrEqualTo($dtr_end_date); $current_dtr_date->addDay()) {
            $current_dtr_date_str = $current_dtr_date->toDateString();

            $current_dtr = $dtrs->firstWhere('dtr_date', $current_dtr_date_str);

            if ($current_dtr && (in_array($submitted_dtr->status, [1, 2, 3]))) {
                $dtr_item = $current_dtr->toArray();
                $dtr_item['in'] = (array) $dtr_item['in'];
                $dtr_item['out'] = (array) $dtr_item['out'];
                $dtr_item['break_start'] = (array) $dtr_item['break_start'];
                $dtr_item['break_end'] = (array) $dtr_item['break_end'];
                $dtr_item['time_off_request'] = !$dtr_item['time_off_request'] ? null : (array) $dtr_item['time_off_request'];
                $dtr_item['date'] = $current_dtr_date_str;
                $dtr_item['dtr_date'] = $current_dtr_date_str;
                $employee_item['dtr_status'] = $submitted_dtr->status;
            }

            $new_time_off_exists = false;
            if (isset($dtr_item)) {
                $time_off_request = \App\Attendance::getEmployeeTimeOff($timeOffsList, $employee_item['employee_id'], $current_dtr_date_str);
                $holiday = \App\Attendance::getHoliday($holidays, $dtr_item);
                if ($submitted_dtr->status !== 3) {
                    if ($time_off_request) {
                        $current_dtr_time_off_id = $dtr_item['time_off_request']['id'] ?? -1;
                        $current_time_off_id = $time_off_request['id'] ?? -1;
                        if ($current_dtr_time_off_id !== $current_time_off_id) {
                            $new_time_off_exists = true;
                            $dtr_item['time_off_request'] = $time_off_request;
                            $dtr_item['holiday'] = $holiday;
                            $dtr_item['rendered_minutes'] = \App\Attendance::convertToMinutes(\App\Attendance::getRenderedSeconds($dtr_item, $schedule));
                            $dtr_item['overtime_minutes'] = \App\Attendance::getOvertimeInMinutes($dtr_item, $schedule, $employee->time_option);
                            $dtr_item['late_minutes'] = \App\Attendance::getLatesInMinutes($dtr_item, $schedule, $employee->time_option);
                            $dtr_item['undertime_minutes'] = \App\Attendance::getUndertimesInMinutes($dtr_item, $schedule, $employee->time_option);
                            $dtr_item['night_differential_minutes'] = \App\Attendance::getNightDifferentialInMinutes($dtr_item);
                            $dtr_item['absence'] = \App\Attendance::getAbsenceCount($dtr_item, $employee->time_option);
                            $dtr_item['overtime'] = \App\Attendance::getOvertimes(
                                $dtr_item['overtime_minutes'],
                                $dtr_item['late_minutes'],
                                $dtr_item,
                                $schedule,
                                $employee->time_option
                            );
                        }
                    } else if ($dtr_item['holiday'] == null && $holiday) {
                        $dtr_item['holiday'] = $holiday;
                        $dtr_item['rendered_minutes'] = \App\Attendance::convertToMinutes(\App\Attendance::getRenderedSeconds($dtr_item, $schedule));
                        $dtr_item['overtime_minutes'] = \App\Attendance::getOvertimeInMinutes($dtr_item, $schedule, $employee->time_option);
                        $dtr_item['late_minutes'] = \App\Attendance::getLatesInMinutes($dtr_item, $schedule, $employee->time_option);
                        $dtr_item['undertime_minutes'] = \App\Attendance::getUndertimesInMinutes($dtr_item, $schedule, $employee->time_option);
                        $dtr_item['night_differential_minutes'] = \App\Attendance::getNightDifferentialInMinutes($dtr_item);
                        $dtr_item['absence'] = \App\Attendance::getAbsenceCount($dtr_item, $employee->time_option);
                        $dtr_item['overtime'] = \App\Attendance::getOvertimes(
                            $dtr_item['overtime_minutes'],
                            $dtr_item['late_minutes'],
                            $dtr_item,
                            $schedule,
                            $employee->time_option
                        );
                    }
                }
            } else {
                $dtr_item['date'] = $current_dtr_date_str;
                $dtr_item['dtr_date'] = $current_dtr_date_str;

                // $biometrics_entries = \App\Attendance::getBiometricsEntries($employee->biometrics, $current_dtr_date);
                $dtr_item['in'] = \App\Attendance::getBiometricsIn($employee->biometrics, $current_dtr_date_str, $employee->end_times_is_next_day); // $biometrics_entries['in'];
                $dtr_item['out'] = \App\Attendance::getBiometricsBreak($employee->biometrics, $current_dtr_date_str, \App\Attendance::BIOMETRIC_TIME_OUT); //$biometrics_entries['out'];
                $dtr_item['break_start'] = \App\Attendance::getBiometricsBreak($employee->biometrics, $current_dtr_date_str, \App\Attendance::BIOMETRIC_BREAK_START); // $biometrics_entries['break_start'];
                $dtr_item['break_end'] = \App\Attendance::getBiometricsOut($employee->biometrics, $current_dtr_date_str, $employee->end_times_is_next_day); // $biometrics_entries['break_end'];

                $dtr_item['time_off_request'] = \App\Attendance::getEmployeeTimeOff($timeOffsList, $employee_item['employee_id'], $current_dtr_date_str);
                $dtr_item['overtime_request'] = \App\Attendance::getEmployeeOvertimeRequest($employee->overtime_requests, $current_dtr_date_str);
                $dtr_item['holiday'] = \App\Attendance::getHoliday($holidays, $dtr_item);
                $dtr_item['is_restday'] = \App\Attendance::getIsRestday($current_dtr_date_str, $schedule);
                $dtr_item['error'] = \App\Attendance::getDtrError($dtr_item);

                $dtr_item['rendered_minutes'] = \App\Attendance::convertToMinutes(\App\Attendance::getRenderedSeconds($dtr_item, $schedule));
                $dtr_item['overtime_minutes'] = \App\Attendance::getOvertimeInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['late_minutes'] = \App\Attendance::getLatesInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['undertime_minutes'] = \App\Attendance::getUndertimesInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['night_differential_minutes'] = \App\Attendance::getNightDifferentialInMinutes($dtr_item);
                $dtr_item['absence'] = \App\Attendance::getAbsenceCount($dtr_item, $employee->time_option);
                $dtr_item['overtime'] = \App\Attendance::getOvertimes(
                    $dtr_item['overtime_minutes'],
                    $dtr_item['late_minutes'],
                    $dtr_item,
                    $schedule,
                    $employee->time_option
                );
            }

            if ($dtr_item['time_off_request'] !== null && $dtr_item['time_off_request']['status'] === 0) {
                $employee_item['dtr_status'] = 1;
            }

            if ($new_time_off_exists) {
                $current_dtr->time_off_request = $time_off_request;
                $current_dtr->save();
            }

            $window_from_current = $current_dtr_date->copy()->addDay('1');
            $biometrics = \App\Biometrics::where('employeeId', '=', $employee->employee_number->id_number)
                    ->where('attendance', '>=', $current_dtr_date_str . ' 04:00')
                    ->where('attendance', '<', $window_from_current->toDateString(). ' 10:00') // plus 30 hours
                    ->orderBy('attendance', 'asc')->get();
            $dtr_item['biometrics'] = $biometrics->values();

            $employee_item['total_rendered_minutes'] += $dtr_item['rendered_minutes'];
            $employee_item['total_overtime_minutes'] += $dtr_item['overtime_minutes'];
            $employee_item['total_late_minutes'] += $dtr_item['late_minutes'];
            $employee_item['total_undertime_minutes'] += $dtr_item['undertime_minutes'];
            $employee_item['absences'] += $dtr_item['absence'];

            $employee_item['total_days_with_late'] += $dtr_item['late_minutes'] === 0 ? 0 : 1;
            $employee_item['total_days_with_undertime'] += $dtr_item['undertime_minutes'] === 0 ? 0 : 1;

            $employee_item['dtrs']->put($dtr_item['date'], $dtr_item);
            unset($dtr_item);
        }
        return $employee_item;
    }

    public function view_employee_attendance_self(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $holidays = \App\Attendance::getHolidays($startDate, $endDate);
        $days = \App\Attendance::getFormattedDtrDays($startDate, $endDate, $holidays);

        $queryEmployee = \App\Employee::with([
            'biometrics' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('attendance', [$startDate . " 00:00:00", $endDate . " 23:59:59"]);
            },
            'overtime_requests' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_time', [$startDate . " 00:00:00", $endDate . " 23:59:59"]);
            }
        ])
            ->where('employees.id', $this->me->employee_details->id)
            ->leftJoin('employment_and_compensation', 'employees.id', '=', 'employment_and_compensation.employee_id')
            ->leftJoin('work_schedules', 'employment_and_compensation.work_schedule_id', '=', 'work_schedules.id')
            ->leftJoin('fixed_daily_hours', 'work_schedules.id', '=', 'fixed_daily_hours.work_schedule_id')
            ->leftJoin('fixed_daily_times', 'work_schedules.id', '=', 'fixed_daily_times.work_schedule_id')
            ->leftJoin('employee_id_number', 'employees.id', 'employee_id_number.employee_id')
            ->select(
                'employees.id',
                'work_schedules.flexible_weekly_hours',
                'work_schedules.time_option',
                'employees.status',
                'fixed_daily_hours.daily_hours',
                'fixed_daily_times.start_times',
                'fixed_daily_times.end_times',
                'fixed_daily_times.grace_periods',
                'fixed_daily_times.end_times_is_next_day',
                'employment_and_compensation.work_schedule_id',
                'employment_and_compensation.employee_id',
                'employee_id_number.id_number'
            )->first();

        $breakTimesList = \App\BreakTime::where('type', 0)->get();
        $timeOffsList = \App\Attendance::getTimeOffs([$queryEmployee->employee_id], $startDate, $endDate);

        $dtrSubmittedList = \App\DtrSubmit::with('dtrs')
            ->where('employee_id', $queryEmployee->employee_id)
            ->whereHas('dtrs', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('dtr_date', [$startDate, $endDate]);
            });
        //\Log::info("dtrSubmittedList" . $dtrSubmittedList);
        $employee = self::getAttendanceEmployeeItemSelf(
            $queryEmployee,
            $startDate,
            $endDate,
            $holidays,
            $breakTimesList,
            $dtrSubmittedList,
            $timeOffsList
        );

        return response()->json(array(
            'holidays' => $holidays,
            'days' => $days,
            'employee' => $employee
        ));
    }

    private static function getAttendanceEmployeeItemSelf($employee, $startDate, $endDate, $holidays, $breakTimesList, $dtrSubmittedList, $timeOffsList)
    {
        $employee_item = array();
        $employee_item['employee_id'] = $employee->employee_id;
        $employee_item['name'] = $employee->name;
        $employee_item['dtrs'] = collect();

        $employee_item['total_rendered_minutes'] = 0;
        $employee_item['total_overtime_minutes'] = 0;
        $employee_item['total_late_minutes'] = 0;
        $employee_item['total_undertime_minutes'] = 0;
        $employee_item['total_days_with_late'] = 0;
        $employee_item['total_days_with_undertime'] = 0;
        $employee_item['absences'] = 0;

        $breaks = $breakTimesList->firstWhere('work_schedule_id', $employee->work_schedule_id);
        $schedule = \App\Attendance::getSchedule($employee, $breaks);

        $current_dtr_date = Carbon::createFromFormat('Y-m-d', $startDate);
        $dtr_end_date = Carbon::createFromFormat('Y-m-d', $endDate);

        $submitted_dtr = $dtrSubmittedList->firstWhere('employee_id', $employee_item['employee_id']);
        $employee_item['dtr_status'] = $submitted_dtr === null ? 0 : $submitted_dtr->status;
        $employee_item['dtr_submit_id'] = $submitted_dtr === null ? null : $submitted_dtr->id;
        $dtrs = $submitted_dtr === null ? collect() : $submitted_dtr->dtrs;

        for ($current_dtr_date; $current_dtr_date->lessThanOrEqualTo($dtr_end_date); $current_dtr_date->addDay()) {
            $current_dtr_date_str = $current_dtr_date->toDateString();

            $current_dtr = $dtrs->firstWhere('dtr_date', $current_dtr_date_str);

            if ($current_dtr && $submitted_dtr->status !== 4) {
                $dtr_item = $current_dtr->toArray();
                $dtr_item['in'] = (array) $dtr_item['in'];
                $dtr_item['out'] = (array) $dtr_item['out'];
                $dtr_item['break_start'] = (array) $dtr_item['break_start'];
                $dtr_item['break_end'] = (array) $dtr_item['break_end'];
                $dtr_item['time_off_request'] = !$dtr_item['time_off_request'] ? null : (array) $dtr_item['time_off_request'];
                $dtr_item['date'] = $current_dtr_date_str;
                $dtr_item['dtr_date'] = $current_dtr_date_str;
                $employee_item['dtr_status'] = $submitted_dtr->status;
            }

            $new_time_off_exists = false;
            if (isset($dtr_item)) {
                $time_off_request = \App\Attendance::getEmployeeTimeOff($timeOffsList, $employee_item['employee_id'], $current_dtr_date_str);
                $holiday = \App\Attendance::getHoliday($holidays, $dtr_item);
                if ($submitted_dtr->status !== 3) {
                    if ($time_off_request) {
                        $current_dtr_time_off_id = $dtr_item['time_off_request']['id'] ?? -1;
                        $current_time_off_id = $time_off_request['id'] ?? -1;
                        if ($current_dtr_time_off_id !== $current_time_off_id) {
                            $new_time_off_exists = true;
                            $dtr_item['time_off_request'] = $time_off_request;
                            $dtr_item['holiday'] = $holiday;
                            $dtr_item['rendered_minutes'] = \App\Attendance::convertToMinutes(\App\Attendance::getRenderedSeconds($dtr_item, $schedule));
                            $dtr_item['overtime_minutes'] = \App\Attendance::getOvertimeInMinutes($dtr_item, $schedule, $employee->time_option);
                            $dtr_item['late_minutes'] = \App\Attendance::getLatesInMinutes($dtr_item, $schedule, $employee->time_option);
                            $dtr_item['undertime_minutes'] = \App\Attendance::getUndertimesInMinutes($dtr_item, $schedule, $employee->time_option);
                            $dtr_item['night_differential_minutes'] = \App\Attendance::getNightDifferentialInMinutes($dtr_item);
                            $dtr_item['absence'] = \App\Attendance::getAbsenceCount($dtr_item, $employee->time_option);
                            $dtr_item['overtime'] = \App\Attendance::getOvertimes(
                                $dtr_item['overtime_minutes'],
                                $dtr_item['late_minutes'],
                                $dtr_item,
                                $schedule,
                                $employee->time_option
                            );
                        }
                    } else if ($dtr_item['holiday'] == null && $holiday) {
                        $dtr_item['holiday'] = $holiday;
                        $dtr_item['rendered_minutes'] = \App\Attendance::convertToMinutes(\App\Attendance::getRenderedSeconds($dtr_item, $schedule));
                        $dtr_item['overtime_minutes'] = \App\Attendance::getOvertimeInMinutes($dtr_item, $schedule, $employee->time_option);
                        $dtr_item['late_minutes'] = \App\Attendance::getLatesInMinutes($dtr_item, $schedule, $employee->time_option);
                        $dtr_item['undertime_minutes'] = \App\Attendance::getUndertimesInMinutes($dtr_item, $schedule, $employee->time_option);
                        $dtr_item['night_differential_minutes'] = \App\Attendance::getNightDifferentialInMinutes($dtr_item);
                        $dtr_item['absence'] = \App\Attendance::getAbsenceCount($dtr_item, $employee->time_option);
                        $dtr_item['overtime'] = \App\Attendance::getOvertimes(
                            $dtr_item['overtime_minutes'],
                            $dtr_item['late_minutes'],
                            $dtr_item,
                            $schedule,
                            $employee->time_option
                        );
                    }
                }
            } else {
                $dtr_item['id'] = $current_dtr->id ?? null;
                $dtr_item['date'] = $current_dtr_date_str;
                $dtr_item['dtr_date'] = $current_dtr_date_str;

                // $biometrics_entries = \App\Attendance::getBiometricsEntries($employee->biometrics, $current_dtr_date);
                $dtr_item['in'] = \App\Attendance::getBiometricsIn($employee->biometrics, $current_dtr_date_str, $employee->end_times_is_next_day); // $biometrics_entries['in'];
                $dtr_item['out'] = \App\Attendance::getBiometricsBreak($employee->biometrics, $current_dtr_date_str, \App\Attendance::BIOMETRIC_TIME_OUT); //$biometrics_entries['out'];
                $dtr_item['break_start'] = \App\Attendance::getBiometricsBreak($employee->biometrics, $current_dtr_date_str, \App\Attendance::BIOMETRIC_BREAK_START); // $biometrics_entries['break_start'];
                $dtr_item['break_end'] = \App\Attendance::getBiometricsOut($employee->biometrics, $current_dtr_date_str, $employee->end_times_is_next_day); // $biometrics_entries['break_end'];

                $dtr_item['time_off_request'] = \App\Attendance::getEmployeeTimeOff($timeOffsList, $employee_item['employee_id'], $current_dtr_date_str);
                $dtr_item['overtime_request'] = \App\Attendance::getEmployeeOvertimeRequest($employee->overtime_requests, $current_dtr_date_str);
                $dtr_item['holiday'] = \App\Attendance::getHoliday($holidays, $dtr_item);
                $dtr_item['is_restday'] = \App\Attendance::getIsRestday($current_dtr_date_str, $schedule);
                $dtr_item['error'] = \App\Attendance::getDtrError($dtr_item);

                $dtr_item['rendered_minutes'] = \App\Attendance::convertToMinutes(\App\Attendance::getRenderedSeconds($dtr_item, $schedule));
                $dtr_item['overtime_minutes'] = \App\Attendance::getOvertimeInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['late_minutes'] = \App\Attendance::getLatesInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['undertime_minutes'] = \App\Attendance::getUndertimesInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['night_differential_minutes'] = \App\Attendance::getNightDifferentialInMinutes($dtr_item);
                $dtr_item['absence'] = \App\Attendance::getAbsenceCount($dtr_item, $employee->time_option);
                $dtr_item['overtime'] = \App\Attendance::getOvertimes(
                    $dtr_item['overtime_minutes'],
                    $dtr_item['late_minutes'],
                    $dtr_item,
                    $schedule,
                    $employee->time_option
                );
            }

            if ($dtr_item['time_off_request'] !== null && $dtr_item['time_off_request']['status'] === 0) {
                $employee_item['dtr_status'] = 1;
            }

            if ($new_time_off_exists) {
                $current_dtr->time_off_request = $time_off_request;
                $current_dtr->save();
            }

            $employee_item['total_rendered_minutes'] += $dtr_item['rendered_minutes'];
            $employee_item['total_overtime_minutes'] += $dtr_item['overtime_minutes'];
            $employee_item['total_late_minutes'] += $dtr_item['late_minutes'];
            $employee_item['total_undertime_minutes'] += $dtr_item['undertime_minutes'];
            $employee_item['absences'] += $dtr_item['absence'];

            $employee_item['total_days_with_late'] += $dtr_item['late_minutes'] === 0 ? 0 : 1;
            $employee_item['total_days_with_undertime'] += $dtr_item['undertime_minutes'] === 0 ? 0 : 1;

            $employee_item['dtrs']->push($dtr_item);
            unset($dtr_item);
        }
        return $employee_item;
    }

    public function submit_dtr(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'dtr_submit_id' => 'sometimes|exists:dtr_submits,id',
            'dtrs' => 'required|array',
            'dtrs.*.id' => 'sometimes|exists:dtrs,id',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('dtr_submit', $this->me->employee_details->id);
        if ($app_flow_id === -1) {
            return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
        }

        $dtr_submit = null;
        if ($request->filled('dtr_submit_id')) {
            $dtr_submit = \App\DtrSubmit::find($request->input('dtr_submit_id'));
        }

        $dtrs = \App\Dtr::whereIn('id', array_column($request->input('dtrs'), 'id'))->get();

        \DB::beginTransaction();
        try {
            if (!$dtr_submit) {
                $dtr_submit = \App\DtrSubmit::create([
                    'employee_id' => $this->me->employee_details->id
                ]);
            }
            $dtr_submit->approval_request_id = $dtr_submit->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'dtr_submit');
            $dtr_submit->status = 2;
            $dtr_submit->save();

            foreach ($request->input('dtrs') as $dtr) {
                $edit_dtr = $dtrs->firstWhere('id', $dtr['id'] ?? null);
                if (!$edit_dtr) {
                    $edit_dtr = new \App\Dtr();
                }

                $edit_dtr->fill($dtr);
                $edit_dtr->dtr_submit_id = $dtr_submit->id;
                $edit_dtr->save();
            }
            \App\Notification::create_hr_notification(
                ['view_employee_dtr', 'approve_dtr'],
                $this->me->name . ' submitted DTR for ' . $this->get_dates_from_dtrs($dtr_submit->dtrs),
                \App\Notification::NOTIFICATION_SOURCE_DTR,
                $dtr_submit->id,
                $dtr_submit
            );


            \DB::commit();
            return response()->json(array("result" => "success", "data" => $dtr_submit));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function edit_dtr(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'dtr_submit_id' => 'sometimes|exists:dtr_submits,id',
            'dtrs' => 'required|array',
            'dtrs.*.id' => 'sometimes|exists:dtrs,id|nullable',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $dtr_submit = null;
        if ($request->filled('dtr_submit_id')) {
            $dtr_submit = \App\DtrSubmit::find($request->input('dtr_submit_id'));
        }

        if ($dtr_submit && ($dtr_submit->status === 2 || $dtr_submit->status === 3)) {
            return response()->json(['error' => 'Unauthorized', 'message' => 'DTR already submitted.'], 400);
        }

        $employee = \App\Employee::where('employees.id', $this->me->employee_details->id)
            ->leftJoin('employment_and_compensation', 'employees.id', '=', 'employment_and_compensation.employee_id')
            ->leftJoin('work_schedules', 'employment_and_compensation.work_schedule_id', '=', 'work_schedules.id')
            ->leftJoin('fixed_daily_hours', 'work_schedules.id', '=', 'fixed_daily_hours.work_schedule_id')
            ->leftJoin('fixed_daily_times', 'work_schedules.id', '=', 'fixed_daily_times.work_schedule_id')
            ->select(
                'employees.id',
                'work_schedules.flexible_weekly_hours',
                'work_schedules.time_option',
                'employees.status',
                'fixed_daily_hours.daily_hours',
                'fixed_daily_times.start_times',
                'fixed_daily_times.end_times',
                'fixed_daily_times.grace_periods',
                'fixed_daily_times.end_times_is_next_day',
                'employment_and_compensation.work_schedule_id',
                'employment_and_compensation.employee_id'
            )->first();

        $breaks = \App\BreakTime::where('work_schedule_id', $employee->work_schedule_id)->first();
        $schedule = \App\Attendance::getSchedule($employee, $breaks);

        $dtrs = \App\Dtr::whereIn('id', array_column($request->input('dtrs'), 'id'))->get();

        \DB::beginTransaction();
        try {
            if (!$dtr_submit) {
                $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('dtr_submit', $this->me->employee_details->id);
                if ($app_flow_id === -1) {
                    return response()->json(['error' => 'validation_failed', 'messages' => 'no approval flow exists'], 400);
                }

                $dtr_submit = \App\DtrSubmit::create([
                    'employee_id' => $this->me->employee_details->id,
                ]);
                $dtr_submit->approval_request_id = $dtr_submit->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                    ->create_approval_flow_for_request($this->me->id, $this->me->employee_details->id, $app_flow_id, 'dtr_submit');
            }
            $dtr_submit->status = 0;
            $dtr_submit->save();

            foreach ($request->input('dtrs') as $dtr) {
                $dtr = (object) $dtr;
                $edit_dtr = $dtrs->firstWhere('id', $dtr->id ?? null);
                if (!$edit_dtr) {
                    $edit_dtr = new \App\Dtr();
                }

                $dtr_item = array();
                $dtr_item['employee_id'] = $this->me->employee_details->id;
                $dtr_item['date'] = $dtr->date;
                $dtr_item['dtr_date'] = $dtr->date;
                $dtr_item['in'] = $dtr->in;
                $dtr_item['break_start'] = $dtr->break_start;
                $dtr_item['break_end'] = $dtr->break_end;
                $dtr_item['out'] = $dtr->out;

                $dtr_item['time_off_request'] = $dtr->time_off_request;
                $dtr_item['overtime_request'] = $dtr->overtime_request;
                $dtr_item['holiday'] = $dtr->holiday;
                $dtr_item['is_restday'] = $dtr->is_restday;
                $dtr_item['error'] = $dtr->error;

                $dtr_item['rendered_minutes'] = \App\Attendance::convertToMinutes(\App\Attendance::getRenderedSeconds($dtr_item, $schedule));
                $dtr_item['overtime_minutes'] = \App\Attendance::getOvertimeInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['late_minutes'] = \App\Attendance::getLatesInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['undertime_minutes'] = \App\Attendance::getUndertimesInMinutes($dtr_item, $schedule, $employee->time_option);
                $dtr_item['night_differential_minutes'] = \App\Attendance::getNightDifferentialInMinutes($dtr_item);
                $dtr_item['absence'] = \App\Attendance::getAbsenceCount($dtr_item, $employee->time_option);
                $dtr_item['overtime'] = \App\Attendance::getOvertimes(
                    $dtr_item['overtime_minutes'],
                    $dtr_item['late_minutes'],
                    $dtr_item,
                    $schedule,
                    $employee->time_option
                );

                $edit_dtr->fill($dtr_item);
                $edit_dtr->dtr_submit_id = $dtr_submit->id;
                $edit_dtr->save();
            }
            \DB::commit();
            return response()->json(array("result" => "success", "data" => $dtr_submit));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function list_requests_for_approver(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $approver_list_query = app('\App\Http\Controllers\AppFlowController')->get_approver_requests_query($this->me->employee_details->id);
        $query = \App\DtrSubmit::joinSub($approver_list_query, 'approvers', function ($join) {
            $join->on('dtr_submit.approval_request_id', '=', 'approvers.approval_request_id');
        })
            ->leftJoin('employment_and_compensation', 'dtr_submits.employee_id', 'employment_and_compensation.employee_id')
            ->leftJoin('departments', 'departments.id', 'employment_and_compensation.department_id')
            ->leftJoin('personal_information', 'dtr_submits.employee_id', 'personal_information.employee_id')
            ->where('approvers.can_approve', '=', '1')
            ->select(
                'dtr_submit.*',
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
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'dtr_submits.*' => 'exists:dtr_submits,id',
            'status' => 'required|in:-1,1'
        ];
        $validator = Validator::make($request->all(), $validator_arr);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        \DB::beginTransaction();
        try {
            $result_dtrs = array();
            $dtr_submits = \App\DtrSubmit::whereIn('id', $request->input('dtr_submits', []))->get();
            $overtime_requests = \App\OvertimeRequest::whereIn('employee_id', $dtr_submits->pluck('employee_id'))->get();
            foreach ($dtr_submits as $dtr) {
                $result = app('\App\Http\Controllers\AppFlowController')->approve_reject_request($dtr->approval_request_id, $this->me->employee_details->id, $request->input('status'));
                if ($result === 'request_approved' || $result === 'request_rejected') {
                    $dtr->status = $result === 'request_approved' ? 3 : 4;
                    $dtr->save();
                    if ($result === 'request_approved') {
                        $this->create_overtime_requests($dtr, $overtime_requests);
                        $this->get_deductions_from_late_absence($dtr);
                    }
                    $employee = \App\Employee::where('id', $dtr->employee_id)->first();
                    \App\Notification::create_user_notification(
                        $employee->users_id,
                        'Your DTR for ' . $this->get_dates_from_dtrs($dtr->dtrs) . ' is ' . ($result === 'request_approved' ? 'Approved' : 'Declined'),
                        \App\Notification::NOTIFICATION_SOURCE_DTR,
                        $dtr->id,
                        $dtr
                    );
                }
                array_push($result_dtrs, array(
                    'id' => $dtr->id,
                    'status' => $dtr->status,
                ));
            }

            \DB::commit();
            return response()->json(array("result" => "Request Successful", "data" => $result_dtrs));
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    private function get_deductions_from_late_absence($dtr_submit)
    {
        $dtrs = $dtr_submit->dtrs;

        $vl_balance = \App\TimeOffBalance::with([
            'requests' => function ($q) {
                $q->where('status', '!=', -1);
            },
            'requests.time_off_details' => function ($q) {
                $q->whereRaw('YEAR(time_off_date)', Carbon::now()->format('y'));
            },
            'adjustments' => function ($q) {
                $q->whereRaw('YEAR(effectivity_date)', Carbon::now()->format('y'));
            },
        ])
            ->where([
                ['employee_id', $dtr_submit->employee_id],
                ['time_off_id', 1]
            ])
            ->first();
        $sl_balance = \App\TimeOffBalance::with([
            'requests' => function ($q) {
                $q->where('status', '!=', -1);
            },
            'requests.time_off_details' => function ($q) {
                $q->whereRaw('YEAR(time_off_date)', Carbon::now()->format('y'));
            },
            'adjustments' => function ($q) {
                $q->whereRaw('YEAR(effectivity_date)', Carbon::now()->format('y'));
            },
        ])
            ->where([
                ['employee_id', $dtr_submit->employee_id],
                ['time_off_id', 2]
            ])
            ->first();

        $vl_balance_remaining = $vl_balance->balance ?? 0;
        $sl_balance_remaining = $sl_balance->balance ?? 0;
        /**
         *[1] if late, deduct sa VL
         *[2] if absent, deduct sa SL.
         *[3] if absent then No SL na, sa VL na mag ddeduct
         */
        foreach ($dtrs as $dtr) {
            if ($dtr->holiday) {
                continue;
            }

            $total_late = $dtr->late_minutes;
            $late_hours = intval($total_late / 60);
            $late_minutes = $total_late - ($late_hours * 60);

            $vl_deduction = \App\Attendance::convertTimeToLeavePoints($late_hours, $late_minutes);
            $vl_late_adjustment_value = 0;
            // if ($vl_balance_remaining >= $vl_deduction)
            if (bccomp($vl_balance_remaining, $vl_deduction, DayFractions::SCALE) >= 0) {
                $vl_late_adjustment_value = $vl_deduction;
                $vl_balance_remaining = bcsub($vl_balance_remaining, $vl_deduction, DayFractions::SCALE);
                $vl_deduction = 0;
            } else {
                $vl_late_adjustment_value = $vl_balance_remaining;
                $vl_balance_remaining = 0;
                $vl_deduction = bcsub($vl_deduction, $vl_late_adjustment_value, DayFractions::SCALE);
            }

            if (bccomp($vl_late_adjustment_value, 0, DayFractions::SCALE) == 1) {
                \App\TimeOffAdjustment::create([
                    'time_off_balance_id' => $vl_balance->id,
                    'adjustment_value' => -1 * $vl_late_adjustment_value,
                    'effectivity_date' => $dtr->dtr_date, // Carbon::now()->toDateString(),
                    'remarks' => 'Deduction due to late on ' . $dtr->dtr_date
                ]);
            }

            $sl_deduction = $dtr->absence;
            $sl_absence_adjustment_value = 0;

            // if ($sl_balance_remaining >= $sl_deduction)
            if (bccomp($sl_balance_remaining, $sl_deduction, DayFractions::SCALE) >= 0) {
                $sl_absence_adjustment_value = intval($sl_deduction);
            } else {
                $sl_absence_adjustment_value = intval($sl_balance_remaining);
            }

            $sl_balance_remaining = bcsub($sl_balance_remaining, $sl_absence_adjustment_value, DayFractions::SCALE);
            $sl_deduction -= $sl_absence_adjustment_value;

            if (bccomp($sl_absence_adjustment_value, 0, DayFractions::SCALE) == 1) {
                \App\TimeOffAdjustment::create([
                    'time_off_balance_id' => $sl_balance->id,
                    'adjustment_value' => -1 * $sl_absence_adjustment_value,
                    'effectivity_date' => $dtr->dtr_date, // Carbon::now()->toDateString(),
                    'remarks' => 'Deduction due to absence on ' . $dtr->dtr_date
                ]);
            }

            $vl_absence_adjustment_value = 0;

            // if ($vl_balance_remaining >= $sl_deduction)
            if (bccomp($vl_balance_remaining, $sl_deduction, DayFractions::SCALE) >= 0) {
                $vl_absence_adjustment_value = intval($sl_deduction);
            } else {
                $vl_absence_adjustment_value = intval($vl_balance_remaining);
            }

            $vl_balance_remaining = bcsub($vl_balance_remaining, $vl_absence_adjustment_value, DayFractions::SCALE);
            $sl_deduction -= $vl_absence_adjustment_value;

            if ($vl_absence_adjustment_value > 0) {
                \App\TimeOffAdjustment::create([
                    'time_off_balance_id' => $vl_balance->id,
                    'adjustment_value' => -1 * $vl_absence_adjustment_value,
                    'effectivity_date' => $dtr->dtr_date, // Carbon::now()->toDateString(),
                    'remarks' => 'Deduction due to absence on ' . $dtr->dtr_date
                ]);
            }

            $late_for_payment_deduction = \App\Attendance::convertLeavePointsToTime($vl_deduction);
            $dtr->late_for_payment_deduction = $late_for_payment_deduction->totalMinutes;
            $dtr->absence_for_payment_deduction = $sl_deduction;

            $dtr->late_for_vl_deduction = $vl_late_adjustment_value;
            $dtr->absence_for_vl_deduction = $vl_absence_adjustment_value;
            $dtr->absence_for_sl_deduction = $sl_absence_adjustment_value;

            $dtr->save();
        }
    }

    private function create_overtime_requests(\App\DtrSubmit $dtr_submit, $overtime_requests)
    {
        $dtrs = $dtr_submit->dtrs->sortBy('dtr_date');
        $start_date = $dtrs->first()->dtr_date;
        $end_date = $dtrs->last()->dtr_date;
        foreach ($dtrs as $time_record) {
            if ($time_record->overtime_minutes === 0) {
                continue;
            }

            if ($time_record->overtime === null || count($time_record->overtime) === 0) {
                continue;
            }

            foreach ($time_record->overtime as $overtime) {
                $overtime = (object) $overtime;
                $overtime_request = null;

                $overtime_request = $overtime_requests->filter(function ($value) use ($overtime, $time_record) {
                    $same_dtr = $time_record->dtr_date === $value->dtr_date;
                    $overtime_start = Carbon::createFromFormat('Y-m-d H:i:s', $overtime->start);
                    $overtime_end = Carbon::createFromFormat('Y-m-d H:i:s', $overtime->end);
                    $overtime_request_start = Carbon::createFromFormat('Y-m-d H:i:s', $value->start_time);
                    $overtime_request_end = Carbon::createFromFormat('Y-m-d H:i:s', $value->end_time);
                    return $same_dtr && ($overtime_start->lessThanOrEqualTo($overtime_request_start) && $overtime_end->greaterThan($overtime_request_start)) ||
                        ($overtime_start->lessThan($overtime_request_end) && $overtime_end->greaterThanOrEqualTo($overtime_request_end));
                })->first();

                // overtimes are only considered if over 30 minutes
                $duration_in_minutes = intval(intval($overtime->duration_in_minutes / 30) * 30);

                if (!$overtime_request) {
                    $app_flow_id = app('\App\Http\Controllers\AppFlowController')->app_flow_id('employee_ot', $dtr_submit->employee_id);
                    if ($app_flow_id === -1) {
                        throw new \Exception('An employee with overtime does not have an approval flow');
                    }
                    $overtime_request = \App\OvertimeRequest::create([
                        'employee_id' =>  $dtr_submit->employee_id,
                        'start_time' => $overtime->start,
                        'end_time' => $overtime->end,
                        'time_in_out' => array(
                            'time_in' => $time_record->in->schedule,
                            'time_out' => $time_record->out->schedule,
                            'break_in' => $time_record->break_end->schedule ?? null,
                            'break_out' => $time_record->break_start->schedule ?? null,
                            'overtime_start' => $overtime->start,
                            'overtime_end' => $overtime->end,
                            'overtime_duration_in_minutes' => $overtime->duration_in_minutes
                        ),
                        'duration_in_minutes' => $duration_in_minutes,
                        'dtr_date' => $time_record->dtr_date
                    ]);
                    $overtime_request->approval_request_id = app('\App\Http\Controllers\AppFlowController')
                        ->create_approval_flow_for_request($this->me->id, $dtr_submit->employee_id, $app_flow_id, 'employee_ot');
                } else {
                    $overtime_request->time_in_out = array(
                        'time_in' => $time_record->in->schedule,
                        'time_out' => $time_record->out->schedule,
                        'break_in' => $time_record->break_end->schedule ?? null,
                        'break_out' => $time_record->break_start->schedule ?? null,
                        'overtime_start' => $overtime->start,
                        'overtime_end' => $overtime->end,
                        'overtime_duration_in_minutes' => $overtime->duration_in_minutes
                    );
                    $overtime_request->duration_in_minutes = $duration_in_minutes;
                }

                $overtime_request->status = -2;
                $overtime_request->save();
            }
        }
    }

    public static function getDtrError($dtr)
    {
        $response = array();

        $numOfIns = count($dtr["ins"]);
        if (count($dtr["ins"]) === 0) {
            $response["date"] = $dtr["date"];
            $response["resultCode"] = "400_1_2_4";
            $response["result"] = "No IN record";
            return $response;
        } else if (count($dtr["outs"]) === 0) {
            $response["date"] = $dtr["date"];
            $response["resultCode"] = "400_1_2_1";
            $response["result"] = "No OUT record";
            return $response;
        }

        $actualIn = Carbon::parse($dtr["ins"][0]["schedule"]);
        $actualOut = Carbon::parse($dtr["outs"][0]["schedule"]);

        if ($actualIn->greaterThanOrEqualTo($actualOut)) {
            $response["date"] = $dtr["date"];
            $response["resultCode"] = "400_1_1_3";
            $response["result"] = "In > Out";
            return $response;
        }

        return $response;
    }

    private function get_dates_from_dtrs($dtrs)
    {
        $start_date = null;
        $end_date = null;
        foreach ($dtrs as $dtr) {
            $date = Carbon::parse($dtr['dtr_date']);
            if ($start_date == null || $start_date > $date) {
                $start_date = $date;
            }
            if ($end_date == null || $end_date < $date) {
                $end_date = $date;
            }
        }
        if ($end_date == $start_date) {
            return $start_date->format('Y-m-d');
        } else {
            return $start_date->format('Y-m-d') . ' to ' . $end_date->format('Y-m-d');
        }
    }
}
