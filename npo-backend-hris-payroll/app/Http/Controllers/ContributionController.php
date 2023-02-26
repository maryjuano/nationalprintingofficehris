<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Contribution;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ContributionController extends Controller
{
    public function list_remittances(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_remittance']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'type' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $query = \App\Employee::with([
            'payroll_employee_logs' => function ($q) {
                $q->max('payroll_period');
            },
            'personal_information',
            'employment_and_compensation',
            'employment_and_compensation.department',
            'employment_and_compensation.section',
            'employment_and_compensation.position',
            'employment_and_compensation.employee_type',
            'employment_and_compensation.salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            }
        ])
            ->leftJoin('personal_information', 'personal_information.employee_id', 'employees.id')
            ->where('employees.status', 1)
            ->select(
                'employees.*',
                'personal_information.last_name'
            );

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        $data = $response['data'];
        $result = array();

        $config_philhealth = $this->get_statutory_config_data('philhealth');
        $config_gsis = $this->get_statutory_config_data('gsis');
        $config_pagibig = $this->get_statutory_config_data('pagibig');

        foreach ($data as $employee) {
            $array = array();
            $array['employee_id'] = $employee->id;
            $array['name'] = $employee->name;
            $array['department'] = $employee->department;
            $array['section'] = $employee->section;
            $array['employee_type'] = $employee->employee_type;

            $salary = $employee->salary;

            if ($request->input('type') === 'pagibig') {
                $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', 'pagibig');
                if ($amount_from_payroll) {
                    $array['personal_share'] = $amount_from_payroll->amount;
                    $pagibig_contribution = $this->get_pagibig_contribution($config_pagibig, $salary, null, null);
                    $array['government_share'] = $pagibig_contribution['government_share'];
                }
                $array['pagibig_number'] = $employee->employment_and_compensation->pagibig_number;
            } else if ($request->input('type') === 'philhealth') {
                $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', 'philhealth');
                if ($amount_from_payroll) {
                    $array['personal_share'] = $amount_from_payroll->amount;
                    $philhealth_contribution = $this->get_philhealth_contribution($config_philhealth, $salary);
                    $array['government_share'] = $philhealth_contribution['government_share'];
                    $array['monthly_premium'] = round($array['personal_share'] + $array['government_share'], 2);
                }
                $array['philhealth_number'] = $employee->employment_and_compensation->philhealth_number;
            } else if ($request->input('type') === 'gsis') {
                $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', 'gsis');
                if ($amount_from_payroll) {
                    $array['personal_share'] = $amount_from_payroll->amount;
                    $gsis_contribution = $this->get_gsis_contribution($config_gsis, $salary);
                    $array['government_share'] = $gsis_contribution['government_share'];
                    $array['ecc'] = $gsis_contribution['ecc'];
                }
                $array['gsis_number'] = $employee->employment_and_compensation->gsis_number;
            } else { // tax
                $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', 'tax');
                if ($amount_from_payroll) {
                    $array['tax'] = $amount_from_payroll->amount;
                }
                $array['tin'] = $employee->employment_and_compensation->tin;
            }
            array_push($result, $array);
        }
        $response['data'] = $result;
        return $response;
    }

    public function read_employee_remittance_ss(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'type' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $year = $request->has('year') ? $request->input('year') : (new \DateTime)->format("Y");

        return $this->get_employee_remittance($this->me->employee_details->id, $request->input('type'), $year);
    }

    public function read_employee_remittance(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized(['view_remittance']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'type' => 'required',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $year = $request->has('year') ? $request->input('year') : (new \DateTime)->format("Y");

        return $this->get_employee_remittance($employee_id, $request->input('type'), $year);
    }

    private function get_employee_remittance($employee_id, $remittance_type, $year)
    {
        $remittances_from_payroll = \App\PayrollEmployeeLog::where([
            ['employee_id', $employee_id],
            ['type_of_string', $remittance_type],
            ['year', $year]
        ])
            ->whereIn('type_of', [4, 5]) // type of (4)contribution or (5)tax
            ->orderBy('payroll_period', 'ASC')
            ->get();

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $employee = \App\Employee::with([
            'payroll_employee_logs' => function ($q) {
                $q->max('payroll_period');
            },
            'personal_information',
            'employment_and_compensation',
            'employment_and_compensation.department',
            'employment_and_compensation.section',
            'employment_and_compensation.position',
            'employment_and_compensation.employee_type',
            'employment_and_compensation.salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            }
        ])
            ->where('employees.id', $employee_id)
            ->first();

        $config_philhealth = $this->get_statutory_config_data('philhealth');
        $config_gsis = $this->get_statutory_config_data('gsis');
        $config_pagibig = $this->get_statutory_config_data('pagibig');

        $remittances_per_month = array();
        foreach ($remittances_from_payroll as $remittance) {
            $month_object = \DateTime::createFromFormat('!m', $remittance->month);
            $month = $month_object->format('F');
            if (!isset($remittances[$month])) {
                if ($remittance_type === 'pagibig') {
                    $remittances_per_month[$month] = array(
                        'PS-SHARE' => 0,
                        'GOV-SHARE' => 0,
                        'TOTAL' => 0
                    );
                } else if ($remittance_type === 'philhealth') {
                    $remittances_per_month[$month] = array(
                        'PS-SHARE' => 0,
                        'GOV-SHARE' => 0,
                        'TOTAL' => 0
                    );
                } else if ($remittance_type === 'gsis') {
                    $remittances_per_month[$month] = array(
                        'PS-SHARE' => 0,
                        'GOV-SHARE' => 0,
                        'ECC' => 0,
                        'TOTAL' => 0
                    );
                } else { // tax
                    $remittances_per_month[$month] = array(
                        'TAX' => 0,
                    );
                }
            }

            $salary = $employee->salary;
            if ($remittance_type === 'pagibig') {
                $pagibig_contribution = $this->get_pagibig_contribution($config_pagibig, $salary, null, null);
                $remittances_per_month[$month] = array(
                    'PS-SHARE' => $remittances_per_month[$month]['PS-SHARE'] + $remittance->amount,
                    'GOV-SHARE' => $remittances_per_month[$month]['GOV-SHARE'] + $pagibig_contribution['government_share'],
                    'TOTAL' => round(($remittances_per_month[$month]['TOTAL'] +
                        ($remittances_per_month[$month]['PS-SHARE'] + $remittance->amount) +
                        ($remittances_per_month[$month]['GOV-SHARE'] + $pagibig_contribution['government_share'])), 2)
                );
            } else if ($remittance_type === 'philhealth') {
                $philhealth_contribution = $this->get_philhealth_contribution($config_philhealth, $salary);
                $remittances_per_month[$month] = array(
                    'PS-SHARE' => $remittances_per_month[$month]['PS-SHARE'] + $remittance->amount,
                    'GOV-SHARE' => $remittances_per_month[$month]['GOV-SHARE'] + $philhealth_contribution['government_share'],
                    'TOTAL' => round(($remittances_per_month[$month]['TOTAL'] +
                        ($remittances_per_month[$month]['PS-SHARE'] + $remittance->amount) +
                        ($remittances_per_month[$month]['GOV-SHARE'] + $philhealth_contribution['government_share'])), 2)
                );
            } else if ($remittance_type === 'gsis') {
                $gsis_contribution = $this->get_gsis_contribution($config_gsis, $salary);
                $remittances_per_month[$month] = array(
                    'PS-SHARE' => $remittances_per_month[$month]['PS-SHARE'] + $remittance->amount,
                    'GOV-SHARE' => $remittances_per_month[$month]['GOV-SHARE'] + $gsis_contribution['government_share'],
                    'ECC' => $gsis_contribution['ecc'],
                    'TOTAL' => round(($remittances_per_month[$month]['TOTAL'] +
                        ($remittances_per_month[$month]['PS-SHARE'] + $remittance->amount) +
                        ($remittances_per_month[$month]['GOV-SHARE'] + $gsis_contribution['government_share']) +
                        $remittances_per_month[$month]['ECC']), 2)
                );
            } else { // tax
                $remittances_per_month[$month] = array(
                    'TAX' => $remittances_per_month[$month]['TAX'] + $remittance->amount,
                );
            }
        }

        $remittances = array();
        foreach ($remittances_per_month as $month => $remittance) {
            array_push($remittances, array(
                'month' => $month,
                'data' => $remittance
            ));
        }

        return response()->json([
            'name' => $employee->name,
            'id_number' => $employee->id_number,
            'position_name' => $employee->position,
            'employee_type_name' => $employee->employee_type,
            'department' => $employee->department,
            'section_name' => $employee->section,
            'salary_grade_id' => $employee->employment_and_compensation->salary_grade_id,
            'remittances' => $remittances
        ]);
    }

    public function list_employee_contributions(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['monitor_employee_contributions']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $query = \App\Employee::with([
            'payroll_employee_logs' => function ($q) {
                $q->max('payroll_period');
            },
            'personal_information',
            'employment_and_compensation',
            'employment_and_compensation.department',
            'employment_and_compensation.section',
            'employment_and_compensation.position',
            'employment_and_compensation.employee_type',
            'employment_and_compensation.salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            }
        ])
            ->leftJoin('personal_information', 'personal_information.employee_id', 'employees.id')
            ->where('employees.status', 1)
            ->select(
                'employees.*',
                'personal_information.last_name'
            );

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        $data = $response['data'];
        $result = array();

        $config_philhealth = $this->get_statutory_config_data('philhealth');
        $config_gsis = $this->get_statutory_config_data('gsis');
        $config_pagibig = $this->get_statutory_config_data('pagibig');

        $contribution_requests = \App\ContributionRequest::where('status', 1)
            ->whereIn('employee_id', $data->pluck('id'))
            ->orderBy('updated_at', 'DESC')
            ->get();

        foreach ($data as $employee) {
            $array = array();
            $salary = $employee->salary;

            $array['employee_id'] = $employee->id;
            $array['name'] = $employee->name;
            $array['department'] = $employee->department;
            $array['employee_type'] = $employee->employee_type;
            $array['position'] = $employee->position;
            $array['section'] = $employee->section;
            $array['salary_grade'] = $employee->employment_and_compensation->salary_grade_id;

            $requests = $contribution_requests->where('employee_id', $employee->id);

            $month_year = array(
                "month_value" => Carbon::now()->format('m'),
                "year" => Carbon::now()->format('Y')
            );
            $gsis_contribution = $this->get_gsis_contribution($config_gsis, $salary);
            $philhealth_contribution = $this->get_philhealth_contribution($config_philhealth, $salary);
            $pagibig_contribution = $this->get_pagibig_contribution($config_pagibig, $salary, $requests, $month_year);

            $array['total_salary'] = $salary;
            $array['gsis'] = $gsis_contribution['personal_share'];
            $array['pagibig'] = $pagibig_contribution['personal_share'];
            $array['philhealth'] = $philhealth_contribution['personal_share'];

            array_push($result, $array);
        }

        $response['data'] = $result;
        return $response;
    }

    public function read_employee_contribution(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized(['monitor_employee_contributions']);
        if ($unauthorized) {
            return $unauthorized;
        }

        return $this->get_employee_contribution($employee_id, $request->input('year'));
    }

    public function read_employee_contribution_ss(Request $request)
    {
        $unauthorized = $this->is_not_authorized();
        if ($unauthorized) {
            return $unauthorized;
        }

        return $this->get_employee_contribution($this->me->employee_details->id, null);
    }

    private function get_employee_contribution($employee_id, $year)
    {
        if (!$year) {
            $year = Carbon::now()->format('Y');
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $employee = \App\Employee::with([
            'payroll_employee_logs' => function ($q) {
                $q->max('payroll_period');
            },
            'personal_information',
            'employment_and_compensation',
            'employment_and_compensation.department',
            'employment_and_compensation.section',
            'employment_and_compensation.position',
            'employment_and_compensation.employee_type',
            'employment_and_compensation.salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            }
        ])
            ->where('employees.id', $employee_id)
            ->first();

        $salary = $employee->salary;

        $contribution_requests = \App\ContributionRequest::where([
            ['employee_id', $employee_id],
            ['status', 1]
        ])
            ->orderBy('updated_at', 'DESC')
            ->get();

        $months = [
            array("year" => $year, "month_value" => 1, "month" => "January"),
            array("year" => $year, "month_value" => 2, "month" => "February"),
            array("year" => $year, "month_value" => 3, "month" => "March"),
            array("year" => $year, "month_value" => 4, "month" => "April"),
            array("year" => $year, "month_value" => 5, "month" => "May"),
            array("year" => $year, "month_value" => 6, "month" => "June"),
            array("year" => $year, "month_value" => 7, "month" => "July"),
            array("year" => $year, "month_value" => 8, "month" => "August"),
            array("year" => $year, "month_value" => 9, "month" => "September"),
            array("year" => $year, "month_value" => 10, "month" => "October"),
            array("year" => $year, "month_value" => 11, "month" => "November"),
            array("year" => $year, "month_value" => 12, "month" => "December"),
        ];

        $config_philhealth = $this->get_statutory_config_data('philhealth');
        $config_gsis = $this->get_statutory_config_data('gsis');
        $config_pagibig = $this->get_statutory_config_data('pagibig');

        $result = array();
        foreach ($months as $month) {
            $gsis_contribution = $this->get_gsis_contribution($config_gsis, $salary);
            $philhealth_contribution = $this->get_philhealth_contribution($config_philhealth, $salary);
            $pagibig_contribution = $this->get_pagibig_contribution($config_pagibig, $salary, $contribution_requests, $month);
            $data = [
                "GSIS-PS" => $gsis_contribution['personal_share'],
                "GSIS-GS" => $gsis_contribution['government_share'],
                "PAGIBIG-PS" => $pagibig_contribution['personal_share'],
                "PAGIBIG-GS" => $pagibig_contribution['government_share'],
                "PH-PS" => $philhealth_contribution['personal_share'],
                "PH-GS" =>  $philhealth_contribution['government_share'],
                "PH-MONTHLY-PREMIUM" => $philhealth_contribution['total'],
                "ECC" => $gsis_contribution['ecc']
            ];
            array_push($result, array("month" => $month['month'], "data" => $data));
        }

        return response()->json([
            'name' => $employee->name,
            'id_number' => $employee->id_number,
            'position_name' => $employee->position,
            'employee_type_name' => $employee->employee_type,
            'department' => $employee->department,
            'section_name' => $employee->section,
            'salary_grade_id' => $employee->employment_and_compensation->salary_grade_id,
            'contributions' => $result
        ]);
    }

    private function get_gsis_contribution($config_gsis, $total_salary)
    {
        if (!$config_gsis) {
            return array(
                'personal_share' => 0,
                'government_share' => 0,
                'ecc' => 0
            );
        }

        return array(
            'personal_share' => round($config_gsis->personal_share * $total_salary, 2),
            'government_share' => round($config_gsis->government_share * $total_salary, 2),
            'ecc' => round($config_gsis->ecc, 2)
        );
    }

    public function get_philhealth_contribution($config_philhealth, $total_salary)
    {
        foreach ($config_philhealth as $row => $value) {
            $min = abs($value->maximum_range);
            $max = abs($value->minimum_range);
            if ($min >= $total_salary && $max <= $total_salary) {
                $config_philhealth_contribution = $value;
                break;
            }
        }

        if (!$config_philhealth_contribution) {
            return array(
                'personal_share' => 0,
                'government_share' => 0,
                'total' => 0
            );
        }

        if ($config_philhealth_contribution->is_max) {
            return array(
                'personal_share' => $config_philhealth_contribution->personal_share[0],
                'government_share' => $config_philhealth_contribution->government_share[0],
                'total' => round(($config_philhealth_contribution->personal_share[0] + $config_philhealth_contribution->government_share[0]), 2)
            );
        } else {
            $personal_share = round(($total_salary * ($config_philhealth_contribution->percentage / 100)) / 2, 2, PHP_ROUND_HALF_DOWN);
            $government_share = round(($total_salary * ($config_philhealth_contribution->percentage / 100)) / 2, 2, PHP_ROUND_HALF_UP);
            $total = round($personal_share + $government_share, 2);
            return array(
                'personal_share' => $personal_share,
                'government_share' => $government_share,
                'total' => $total
            );
        }
    }

    public function get_pagibig_contribution($config_pagibig, $total_salary, $contribution_requests, $month_year)
    {
        foreach ($config_pagibig as $row => $value) {
            $min = abs($value->maximum_range);
            $max = abs($value->minimum_range);
            if ($min >= $total_salary && $max <= $total_salary) {
                $config_pagibig_contribution = $value;
                break;
            }
        }

        if (!$config_pagibig_contribution) {
            return array(
                'personal_share' => 0,
                'government_share' => 0
            );
        }

        $personal_share = $config_pagibig_contribution->personal_share;
        $pagibig_request = !$contribution_requests ? null : $contribution_requests
            ->where('contribution_type', 'pagibig')
            ->where('approved_at', '<=', Carbon::createFromFormat('d m Y', '1 ' . $month_year['month_value'] . ' ' . $month_year['year'])->startOfDay())
            ->first();

        if ($pagibig_request) {
            $personal_share = $pagibig_request->amount;
        }

        // $contribution_from_payroll = \App\PayrollEmployeeLog::where([
        //     ['employee_id', $id],
        //     ['type_of_string', 'pagibig'],
        //     ['year', $date['year']]
        // ]);
        // if ($contribution_from_payroll->exists()) {
        //     $personal_share = (float) $contribution_from_payroll->sum('amount');
        // }

        return array(
            'personal_share' => $personal_share,
            'government_share' => $config_pagibig_contribution->government_share
        );
    }

    private function get_statutory_config_data($statutory)
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

    public function get_years_with_contributions(Request $request, $employee_id)
    {
        $payroll_years = \App\PayrollEmployeeLog::where('employee_id', $employee_id)
            ->distinct()->orderBy('year', 'asc')->get(['year'])->pluck('year');
        return $payroll_years;
    }

    public function export_remittances(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_remittance']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $data = \App\Employee::with([
            'payroll_employee_logs' => function ($q) {
                $q->max('payroll_period');
                if (request('type') == '1') {
                    $q->whereMonth('payroll_period', '=', request('month'));
                    $q->whereYear('payroll_period', '=', request('year'));
                } else {
                    $q->whereYear('payroll_period', '=', request('year'));
                }
                if (COUNT(request('ids', [])) > 0) {
                    $q->whereIn('employee_id', request('ids'));
                }
            },
            'personal_information',
            'employment_and_compensation',
            'employment_and_compensation.department',
            'employment_and_compensation.section',
            'employment_and_compensation.position',
            'employment_and_compensation.employee_type',
            'employment_and_compensation.salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            }
        ])
        ->leftJoin('personal_information', 'personal_information.employee_id', 'employees.id')
        ->where('employees.status', 1)
        ->orderBy('personal_information.last_name')
        ->select('employees.*')
        ->get();

        $results = array();
        $config_philhealth = $this->get_statutory_config_data('philhealth');
        $config_gsis = $this->get_statutory_config_data('gsis');
        $config_pagibig = $this->get_statutory_config_data('pagibig');
        $index = 1;
        foreach ($data as $employee) {
            if (COUNT($employee->payroll_employee_logs) > 0) {
                $array = array();
                $array['id'] = $index;
                $array['first_name'] = $employee->personal_information->first_name;
                $array['middle_name'] = $employee->personal_information->middle_name;
                $array['last_name'] = $employee->personal_information->last_name;
                $array['name_extension'] = $employee->personal_information->name_extension;

                $array['pagibig_number'] = $employee->employment_and_compensation->pagibig_number;
                $array['gsis_number'] = $employee->employment_and_compensation->gsis_number;
                $array['philhealth_number'] = $employee->employment_and_compensation->philhealth_number;
                $array['tin'] = $employee->employment_and_compensation->tin;

                $salary = $employee->salary;

                if (request('type') === '1') {
                    $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', 'pagibig');
                    if ($amount_from_payroll) {
                        $array['pagibig_ps'] =  $amount_from_payroll->amount;
                        $pagibig_contribution = $this->get_pagibig_contribution($config_pagibig, $salary, null, null);
                        $array['pagibig_gs'] = $pagibig_contribution['government_share'];
                    }
                } else {
                    $amount_from_payroll = $employee->payroll_employee_logs->Where('type_of_string', 'pagibig');
                    $array['pagibig_ps'] =  $amount_from_payroll->sum('amount');
                    $pagibig_contribution = $this->get_pagibig_contribution($config_pagibig, $salary, null, null);
                    $array['pagibig_gs'] = $pagibig_contribution['government_share'] * COUNT($amount_from_payroll);
                }

                if (request('type') === '1') {
                    $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', 'philhealth');
                    if ($amount_from_payroll) {
                        $array['philhealth_ps'] = $amount_from_payroll->amount;
                        $philhealth_contribution = $this->get_philhealth_contribution($config_philhealth, $salary);
                        $array['philhealth_gs'] = $philhealth_contribution['government_share'];
                    }
                } else {
                    $amount_from_payroll = $employee->payroll_employee_logs->Where('type_of_string', 'philhealth');
                    $array['philhealth_ps'] =  $amount_from_payroll->sum('amount');
                    $pagibig_contribution =  $this->get_philhealth_contribution($config_philhealth, $salary);
                    $array['philhealth_gs'] = $pagibig_contribution['government_share'] * COUNT($amount_from_payroll);
                }

                if (request('type') === '1') {
                    $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', 'gsis');
                    if ($amount_from_payroll) {
                        $array['gsis_ps'] = $amount_from_payroll->amount;
                        $gsis_contribution = $this->get_gsis_contribution($config_gsis, $salary);
                        $array['gsis_gs'] = $gsis_contribution['government_share'];
                        $array['gsis_ecc'] = $gsis_contribution['ecc'];
                    }
                } else {
                    $amount_from_payroll = $employee->payroll_employee_logs->Where('type_of_string', 'gsis');
                    $array['gsis_ps'] = $amount_from_payroll->sum('amount');
                    $gsis_contribution = $this->get_gsis_contribution($config_gsis, $salary);
                    $array['gsis_gs'] = $gsis_contribution['government_share'] * COUNT($amount_from_payroll);
                    $array['gsis_ecc'] = $gsis_contribution['ecc'] * COUNT($amount_from_payroll);
                }

                if (request('type') === '1') {
                    $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', 'tax');
                    if ($amount_from_payroll) {
                        $array['tax'] = $amount_from_payroll->amount;
                    }
                } else {
                    $amount_from_payroll = $employee->payroll_employee_logs->Where('type_of_string', 'tax');
                    $array['tax'] = $amount_from_payroll->sum('amount');
                }

                foreach ($employee->payroll_employee_logs as $log) {
                    if ($log['type_of'] == 2 || $log['type_of'] == 6) {
                        if (request('type') === '1') {
                            $amount_from_payroll = $employee->payroll_employee_logs->firstWhere('type_of_string', $log['type_of_string']);
                            $array['loan_deductions'][$log['type_of_string']] = $log['amount'];
                        } else {
                            $amount_from_payroll = $employee->payroll_employee_logs->Where('type_of_string', $log['type_of_string']);
                            $array['loan_deductions'][$log['type_of_string']] = $amount_from_payroll->sum('amount');
                        }
                    }
                }
                array_push($results, $array);
                $index++;
            }
        }

        $contributionHelper = new Contribution();
        $headers = [
            'Content-Type' => 'application/xlsx'
        ];
        $dateTitle = "";
        $months = [
            'January', 'Febuary', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        if (request('type') === '1') $dateTitle =  "" . $months[request('month', 0) - 1] . '-' . request('year');
        else $dateTitle =  "" . request('year');
        $additionalColumn = \App\PayrollEmployeeLog::whereIn('type_of', array(2, 6))->groupBy('type_of_string')->select('type_of_string')->get();
        $columns = $additionalColumn->map(function ($item, $key) {
            return strtoupper($item->type_of_string);
        });
        $file = $contributionHelper->exportRemittances($results, $dateTitle, $columns);

        return $file;
    }
}
