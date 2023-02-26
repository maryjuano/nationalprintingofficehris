<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use \App\Adjustment;

class EmployeeStubController extends Controller
{
    public function read_employee_stub(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized(['view_employee_stub']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $employee_stub = \App\EmployeeStubList::with([
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
            'employment_type'
        ])
        ->where('employee_id', $employee_id)
        ->first();

        $config_philhealth = $this->get_statutory_config_data('philhealth');
        $config_gsis = $this->get_statutory_config_data('gsis');
        $config_pagibig = $this->get_statutory_config_data('pagibig');
        $all_adjustments = \App\Adjustment::all();
        $this->enrich_employee_stub($employee_stub, $config_philhealth, $config_gsis, $config_pagibig, $all_adjustments);
        return response()->json(array("result" => "success", "data" => $employee_stub));
    }

    public function list_employee_stubs(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['view_employee_stub']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $active_salary_tranche = \App\SalaryTranche::whereDate('effectivity_date', '<=', Carbon::now())
            ->orderBy('effectivity_date', 'desc')->first();
        if (!$active_salary_tranche) {
            return response()->json(array('result' => 'error', 'message' => 'No active salary tranche found.'), 400);
        }

        $employees_query = \App\EmployeeStubList::with([
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
            'salary' => function ($q) use ($active_salary_tranche) {
                $q->where('salary_tranche_id', $active_salary_tranche->id);
            },
            'employment_type'
        ]);

        $ALLOWED_FILTERS = ['department_name', 'position_name', 'employee_type_name'];
        $SEARCH_FIELDS = ['first_name', 'last_name', 'middle_name', 'section_name', 'department_name'];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($employees_query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);
        $employees_stubs = $response['data'];

        // $config_tax_table = $this->get_statutory_config_data('tax');
        $config_philhealth = $this->get_statutory_config_data('philhealth');
        $config_gsis = $this->get_statutory_config_data('gsis');
        $config_pagibig = $this->get_statutory_config_data('pagibig');
        $all_adjustments = \App\Adjustment::all();

        foreach ($employees_stubs as $employee_stub) {
            $this->enrich_employee_stub($employee_stub, $config_philhealth, $config_gsis, $config_pagibig, $all_adjustments);
        }

        return response()->json($response);
    }

    public function save_default_employee_stub(Request $request, $employee_id)
    {
        $unauthorized = $this->is_not_authorized(['edit_employee_stub']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $employee_stub = \App\EmployeeStub::firstOrCreate(['employee_id' => $employee_id]);
        $employee_stub->earnings = $request->filled('earnings') ? $request->input('earnings') : [];
        $employee_stub->deductions = $request->filled('deductions') ? $request->input('deductions') : [];
        $employee_stub->contributions = $request->filled('contributions') ? $request->input('contributions') : [];
        $employee_stub->loans = $request->filled('loans') ? $request->input('loans') : [];
        $employee_stub->reimbursements = $request->filled('reimbursements') ? $request->input('reimbursements') : [];
        $employee_stub->save();
    }

    private function enrich_employee_stub($employee_stub, $config_philhealth, $config_gsis, $config_pagibig, $all_adjustments)
    {
        $adjustment_lookup = $all_adjustments->keyBy('adjustment_name');
        $adjustments = [];
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_PERA_ALLOWANCE]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_PAG_IBIG]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_PHILHEALTH]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_GSIS]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_NAPOWA_DUE]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_MID_YEAR_BONUS]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_YEAR_END_BONUS]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_CASH_GIFT]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_CLOTHING_ALLOWANCE]->id);
        array_push($adjustments, $adjustment_lookup[\App\Adjustment::CONST_PEI]->id);

        $new_employee_stub = $employee_stub;
        $payrun_controller = new \App\Http\Controllers\PayrollRunController();
        $employee_is_temporary =
            $employee_stub->employment_type->employee_type_id === \App\EmployeeType::COS ||
            $employee_stub->employment_type->employee_type_id === \App\EmployeeType::JOB_ORDER;
        $salary_with_step = $employee_is_temporary ?
            $employee_stub->employment_type->salary_rate :
            $employee_stub->salary->step[$employee_stub->step_increment ?? 0];
        if ($salary_with_step == null) {
            $salary_with_step = 0;
        }

        $new_employee_stub['earnings'] = $new_employee_stub['earnings'] ?? [];
        $new_employee_stub['contributions'] = $new_employee_stub['contributions'] ?? [];
        $new_employee_stub['deductions'] = $new_employee_stub['deductions'] ?? [];
        $new_employee_stub['reimbursements'] = $new_employee_stub['reimbursements'] ?? [];
        $new_employee_stub['taxes'] = [];

        $basic_earnings = $payrun_controller->get_earnings($salary_with_step);
        $new_employee_stub['earnings'] = $this->replace_or_add($new_employee_stub['earnings'] ,$basic_earnings, true);
        $new_employee_stub['loans'] = $payrun_controller->get_loans($new_employee_stub);
        $new_employee_stub['basic_pay'] = $salary_with_step;

        //$new_employee_stub['tax'] = $tax;

        // process all adjustments (earnings, deductions, statutory, taxes)
        // almost the same in EmployeeeStubController
        foreach ($adjustments as $adjustment_id) {
            $adjustment_lookup = $all_adjustments->keyBy('id');
            $adjustment = $adjustment_lookup[$adjustment_id];

            if ($adjustment->type == Adjustment::CONST_TYPE_EARNINGS) {
                if ($adjustment->adjustment_name == Adjustment::CONST_YEAR_END_BONUS || $adjustment->adjustment_name == Adjustment::CONST_MID_YEAR_BONUS) {
                    $value = [
                        'title' => $adjustment->adjustment_name,
                        'id' => $adjustment->id,
                        'short_name' => $adjustment->short_name,
                        'amount' => $salary_with_step
                    ];
                    $new_employee_stub['earnings'] = $this->replace_or_add($new_employee_stub['earnings'], $value, true);
                }
                else if ($adjustment->adjustment_name == Adjustment::CONST_PREMIUM) {
                    $value =[
                        'title' => $adjustment->adjustment_name,
                        'id' => $adjustment->id,
                        'short_name' => $adjustment->short_name,
                        'amount' => $new_employee_stub['basic_pay'] * $adjustment->default_amount / 100
                    ];
                    $new_employee_stub['earnings'] = $this->replace_or_add($new_employee_stub['earnings'], $value, true);
                }
                else {
                    $value = [
                        'title' => $adjustment->adjustment_name,
                        'id' => $adjustment->id,
                        'short_name' => $adjustment->short_name,
                        'amount' => $adjustment->default_amount
                    ];
                }
                $new_employee_stub['earnings'] = $this->replace_or_add($new_employee_stub['earnings'], $value, false);
            }
            else if ($adjustment->type == Adjustment::CONST_TYPE_DEDUCTIONS) {
                $value = [
                    'title' => $adjustment->adjustment_name,
                    'id' => $adjustment->id,
                    'short_name' => $adjustment->short_name,
                    'amount' => $adjustment->default_amount
                ];
                $new_employee_stub['deductions'] = $this->replace_or_add($new_employee_stub['deductions'],$value , false);
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
                    $value = $payrun_controller->get_pagibig_contribution($new_employee_stub, $config_pagibig, $salary_with_step, $adjustment);
                }
                else if ($adjustment->adjustment_name == Adjustment::CONST_GSIS) {
                    $value = $payrun_controller->get_gsis_contribution($new_employee_stub, $config_gsis, $salary_with_step, $adjustment);
                }
                else if ($adjustment->adjustment_name == Adjustment::CONST_PHILHEALTH) {
                    $value = $payrun_controller->get_philhealth_contribution($new_employee_stub, $config_philhealth, $salary_with_step, $adjustment);
                }
                else {
                    $value = [
                        'title' => $adjustment->adjustment_name,
                        'id' => $adjustment->id,
                        'short_name' => $adjustment->short_name,
                        'amount' => $adjustment->default_amount
                    ];
                    $value = $payrun_controller->get_from_stub($new_employee_stub['contributions'], $value);
                }
                $new_employee_stub['contributions'] = $this->replace_or_add($new_employee_stub['contributions'], $value, true);
            }
        }

        unset($new_employee_stub['latest_pagibig_request']);
        unset($new_employee_stub['loan_requests']);
        unset($new_employee_stub['salary']);
        unset($new_employee_stub['salary_grade']);
        unset($new_employee_stub['step_increment']);
        unset($new_employee_stub['updated_at']);

        // overwrite values saved from employee_stub

        return $new_employee_stub;
    }

    private function get_pagibig_contribution($employee_stub, $config_pagibig, $salary)
    {
        foreach ($config_pagibig as $row => $value) {
            $min = abs($value->maximum_range);
            $max = abs($value->minimum_range);
            if ($min >= $salary && $max <= $salary) {
                $config_pagibig_contribution = $value;
                break;
            }
        }

        $contributions = $employee_stub['contribution'] ?? [];
        $pagibig_entry_index = array_search('pagibig', array_column($contributions, 'title'));

        $approved_request = $employee_stub->latest_pagibig_request;

        if (
            $pagibig_entry_index !== false &&
            (!$config_pagibig_contribution || $employee_stub->updated_at > $config_pagibig_contribution->updated_at) &&
            (!$approved_request || $employee_stub->updated_at > $approved_request->updated_at)
        ) {
            return $contributions[$pagibig_entry_index];
        } else if (
            $approved_request &&
            (!$config_pagibig_contribution || $approved_request->updated_at > $config_pagibig_contribution->updated_at)
        ) {
            return (object) array(
                "title" => "pagibig",
                "amount" => $approved_request['amount']
            );
        } else {
            return (object) array(
                "title" => "pagibig",
                "amount" => !$config_pagibig_contribution ? 0 : (!$config_pagibig_contribution->is_percentage ? $config_pagibig_contribution->personal_share
                    : round($config_pagibig_contribution->personal_share * $salary, 2))
            );
        }
    }

    private function get_gsis_contribution($employee_stub, $config_gsis, $salary)
    {
        $contributions = $employee_stub['contribution'] ?? [];
        $gsis_entry_index = array_search('gsis', array_column($contributions, 'title'));
        if (
            $gsis_entry_index !== false &&
            (!$config_gsis || $employee_stub->updated_at > $config_gsis->updated_at)
        ) {
            return $contributions[$gsis_entry_index];
        } else {
            return (object) array(
                "title" => "gsis",
                "amount" => !$config_gsis ? 0 : round($config_gsis->personal_share * $salary, 2)
            );
        }
    }

    private function get_philhealth_contribution($employee_stub, $config_philhealth, $salary)
    {
        foreach ($config_philhealth as $row => $value) {
            $min = abs($value->maximum_range);
            $max = abs($value->minimum_range);
            if ($min >= $salary && $max <= $salary) {
                $config_philhealth_contribution = $value;
                break;
            }
        }

        $contributions = $employee_stub['contribution'] ?? [];
        $philhealth_entry_index = array_search('philhealth', array_column($contributions, 'title'));
        if (
            $philhealth_entry_index !== false &&
            (!$config_philhealth_contribution || $employee_stub->updated_at > $config_philhealth_contribution->updated_at)
        ) {
            return $contributions[$philhealth_entry_index];
        } else {
            return (object) array(
                "title" => "philhealth",
                "amount" => !$config_philhealth_contribution ? 0 : round($config_philhealth_contribution->percentage * $salary / 100 / 2, 2, PHP_ROUND_HALF_DOWN)
            );
        }
    }

    // private function get_tax($config_tax_table, $total_salary) old tax function remove later written Jan 2, 2021
    // {
    //     $annual_rate = $total_salary * 12;
    //     $config_tax = $config_tax_table
    //         ->where('lowerLimit', '<', $annual_rate)
    //         ->where('upperLimit', '>=', $annual_rate)
    //         ->first();
    //     if ($config_tax) {
    //         $tax = ($annual_rate - $config_tax->lowerLimit) * ($config_tax->percentage / 100);
    //     } else {
    //         $tax = 0;
    //     }

    //     return array(
    //         'title' => 'tax',
    //         'amount' => round($tax / 12, 2)
    //     );
    // }
    // private function get_tax($config_tax_table, $basicSalary, $contrib, $napowaDue, $empType)
    // {
    //     $tax = 0;
    //     $annualSalary = $basicSalary * 12;
    //     $annualContrib = $contrib * 12;
    //     $annualNAPOWAdue = $napowaDue * 12;
    //     $totStatutories = $annualContrib + $annualNAPOWAdue;
    //     $thirteenMonth = $empType === 1 ? $basicSalary * 2 : 0; //13/14 month pay
    //     $annual_rate = $annualSalary + $thirteenMonth - $totStatutories;
    //     $config_tax = $config_tax_table
    //         ->where('lowerLimit', '<', $annual_rate)
    //         ->where('upperLimit', '>=', $annual_rate)
    //         ->first();
    //     if ($config_tax) {
    //         $taxableIncome = $annual_rate - $config_tax->lowerLimit;
    //         $taxPercentage = ($config_tax->percentage / 100);
    //         $tax = $taxableIncome * $taxPercentage;
    //         // $tax = ($annual_rate - $config_tax->lowerLimit) * ($config_tax->percentage / 100);
    //     } else {
    //         $tax = 0;
    //     }

    //     return array(
    //         'title' => 'tax',
    //         'amount' => round($tax / 12, 2),
    //         'taxableIncome' => $taxableIncome,
    //         'rate' => $config_tax->percentage,
    //         'constant' => $config_tax->constant
    //     );
    // }
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

    private function get_total($data = [])
    {
        $sum = (float) 0;
        foreach ($data as $datum) {
            $value = (object) $datum;
            $sum += (float) $value->amount ?? 0;
        }
        return $sum;
    }
    public function replace_or_add($arr, $value, $is_replace=false){
        $key = 'title';
        for($i=0; $i<sizeof($arr); $i++) {
            if ($arr[$i][$key] == $value[$key]) {
                if ($is_replace) {
                    $arr[$i] = $value;
                }
                // use default if not $is_replace
                return $arr;
            }
        }
        // not found so add
        array_push($arr, $value);
        return $arr;
    }

}
