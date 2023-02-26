<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PayrollUtilController extends Controller
{

    public const REPORT_TYPE_FIRST = 1;
    public const REPORT_TYPE_SECOND = 2;
    public const REPORT_TYPE_FULL = 0;
    public const EXCLUDE = ['keys', 'all'];

    public const PERA_LOAN_CATEGORIES = ['NPOMPC', 'NHMFC'];

    public function create_totals_for_reports($data)
    {
        $totals = [
            'earnings' => [],
            'earnings_underpaid' => [],
            'earnings_overpaid' => [],
            'deductions' => [],
            'pera_deductions' => [],
            'taxes' => [],
            'net_amount' => 0
        ];
        foreach ($data as $dept => $dept_values) {
            foreach (['earnings', 'earnings_underpaid', 'earnings_overpaid', 'deductions', 'pera_deductions', 'taxes'] as $target) {
                foreach ($data[$dept][$target] as $key => $value) {
                    if (isset($totals[$target][$key])) {
                        $totals[$target][$key] += $value;
                    } else {
                        $totals[$target][$key] = $value;
                    }
                }
            }
            $totals['net_amount'] += $data[$dept]['net_amount'];
        }
        return $totals;
    }

    public function create_total_by_pap_code($data)
    {
        $result = [];
        $departments = \App\Department::orderBy('pap_code')->get();
        foreach ($departments as $department) {
            if (!isset($result[$department->pap_code])) {
                $result[$department->pap_code] = [
                    'earnings' => [],
                    'earnings_underpaid' => [],
                    'earnings_overpaid' => [],
                    'deductions' => [],
                    'pera_deductions' => [],
                    'taxes' => [],
                    'net_amount' => 0
                ];
            }
            $pap = $department->pap_code;
            if (isset($data[$department->department_name])) {
                $dept = $department->department_name;
                foreach (['earnings', 'earnings_underpaid', 'earnings_overpaid', 'deductions', 'pera_deductions', 'taxes'] as $target) {
                    foreach ($data[$dept][$target] as $key => $value) {
                        if (isset($result[$pap][$target][$key])) {
                            $result[$pap][$target][$key] += $value;
                        } else {
                            $result[$pap][$target][$key] = $value;
                        }
                    }
                }
                $result[$pap]['net_amount'] += $data[$dept]['net_amount'];
            }
        }
        return $result;
    }

    public function organize_payrun_for_reports($payruns, $type = self::REPORT_TYPE_FULL)
    {
        $result = [];
        $keys = [
            'earnings' => [],
            'earnings_underpaid' => [],
            'earnings_overpaid' => [],
            'deductions' => [],
            'pera_deductions' => [],
            'taxes' => [],
        ];
        foreach ($payruns as $payrun) {
            $payStructure = collect($this->performComputationOnPaystructure($payrun->pay_structure));
            foreach ($payStructure as $employee) {
                $keys['earnings'] = $this->addUniqueTitles($keys['earnings'], $employee['earnings']);
                $keys['earnings'] = $this->addUniqueTitles($keys['earnings'], $employee['reimbursements']);
                $keys['earnings'] = $this->addUniqueTitles($keys['earnings'], $this->add_prefix($employee['contributions_refund'], \App\Adjustment::PREFIX_REFUND));
                $keys['earnings'] = $this->addUniqueTitles($keys['earnings'], $this->add_prefix($employee['loans_refund'], \App\Adjustment::PREFIX_REFUND));
                $keys['earnings_underpaid'] = $this->addUniqueTitles($keys['earnings_underpaid'], $this->add_prefix($employee['earnings_underpaid'], \App\Adjustment::PREFIX_UNDERPAID));
                $keys['earnings_overpaid'] = $this->addUniqueTitles($keys['earnings_overpaid'], $this->add_prefix($employee['earnings_overpaid'], \App\Adjustment::PREFIX_OVERPAID));
                $keys['taxes'] = $this->addUniqueTitles($keys['taxes'], $employee['taxes']);
                $keys['deductions'] = $this->addUniqueTitles($keys['deductions'], $employee['deductions']);
                $keys['deductions'] = $this->addUniqueTitles($keys['deductions'], $employee['contributions']);
                $keys['deductions'] = $this->addUniqueTitles($keys['deductions'], $this->add_prefix($employee['contributions_arrear'], \App\Adjustment::PREFIX_ARREAR));
                // loans need to exclude particular categories to add them to pera_deductions
                foreach ($employee['loans'] as $loan) {
                    if (!isset($loan['category'])) {
                        $keys['deductions'] = $this->addUniqueTitles($keys['deductions'], [$loan]);
                    } elseif (in_array($loan['category'], self::PERA_LOAN_CATEGORIES)) {
                        $keys['pera_deductions'] = $this->addUniqueTitles($keys['pera_deductions'], [$loan]);
                    } else {
                        $keys['deductions'] = $this->addUniqueTitles($keys['deductions'], [$loan]);
                    }
                }
            }
            // $deptArr = $payStructure->unique('department_name')
            //     ->pluck('department_name')
            //     ->flatten();
            $departments = \App\Department::orderBy('pap_code')->get();
            foreach ($departments as $department) {
                $dept = $department->department_name;
                $pay_structure = $this->filterByDept($payStructure, $dept);

                if (array_key_exists($dept, $result)) {
                    // existing. (TODO: This will only happen for multiple payruns in a report)
                } else {
                    // new
                    $result[$dept] = $this->convertPayStructureForReports($pay_structure, $type);
                }
            }
        }
        return [
            'data' => $result,
            'keys' => $keys,
            'type' => $type
        ];
    }

    private function filterByDept($payStructure, $dept)
    {
        $filtered = $payStructure->filter(function ($value, $key) use ($dept) {
            return $value['department_name'] === $dept;
        });
        return $filtered;
    }

    private function convertPayStructureForReports($pay_structure, $type)
    {
        $data = [
            'earnings' => ['all' => 0],
            'earnings_underpaid' => ['all' => 0],
            'earnings_overpaid' => ['all' => 0],
            'deductions' => ['all' => 0],
            'pera_deductions' => ['all' => 0],
            'taxes' => ['all' => 0],
            // aggregates
            'basic_gross_1' => 0,
            'basic_gross_2' => 0
        ];
        foreach ($pay_structure as $employee) {
            // iterate through each employee

            // aggregates
            $data['basic_gross_1'] += $employee['basic_gross_1'];
            $data['basic_gross_2'] += $employee['basic_gross_2'];
            // values
            $data['earnings'] = $this->addToTotal($data['earnings'], $employee['earnings']);
            $data['earnings'] = $this->addToTotal($data['earnings'], $employee['reimbursements']);
            $data['earnings'] = $this->addToTotal($data['earnings'], $this->add_prefix($employee['contributions_refund'], \App\Adjustment::PREFIX_REFUND));
            $data['earnings'] = $this->addToTotal($data['earnings'], $this->add_prefix($employee['loans_refund'], \App\Adjustment::PREFIX_REFUND));
            $data['earnings_underpaid'] = $this->addToTotal($data['earnings_underpaid'], $this->add_prefix($employee['earnings_underpaid'], \App\Adjustment::PREFIX_UNDERPAID));
            $data['earnings_overpaid'] = $this->addToTotal($data['earnings_overpaid'], $this->add_prefix($employee['earnings_overpaid'], \App\Adjustment::PREFIX_OVERPAID));
            $data['taxes'] = $this->addToTotal($data['taxes'], $employee['taxes']);
            $data['deductions'] = $this->addToTotal($data['deductions'], $employee['deductions']);
            $data['deductions'] = $this->addToTotal($data['deductions'], $employee['contributions']);
            $data['deductions'] = $this->addToTotal($data['deductions'], $this->add_prefix($employee['contributions_arrear'], \App\Adjustment::PREFIX_ARREAR));
            // loans need to exclude particular categories to add them to pera_deductions
            foreach ($employee['loans'] as $loan) {
                if (!isset($loan['category'])) {
                    $data['deductions'] = $this->addToTotal($data['deductions'], [$loan]);
                } elseif (in_array($loan['category'], self::PERA_LOAN_CATEGORIES)) {
                    $data['pera_deductions'] = $this->addToTotal($data['pera_deductions'], [$loan]);
                } else {
                    $data['deductions'] = $this->addToTotal($data['deductions'], [$loan]);
                }
            }
        }
        // post process based on $type
        Log::debug($type);
        if ($type == self::REPORT_TYPE_FIRST) {
            $data['earnings']['all'] = 0;
            foreach ($data['earnings'] as $key => $value) {
                Log::debug($key);
                if (in_array($key, self::EXCLUDE)) {
                    // do nothing
                } elseif ($key == \App\Adjustment::CONST_BASIC_PAY) {
                    $data['earnings'][$key] = $data['basic_gross_1'];
                    $data['earnings']['all'] += $data['basic_gross_1'];
                } else {
                    $data['earnings'][$key] = $value / 2;
                    $data['earnings']['all'] += $value / 2;
                }
            }
            $data['earnings_underpaid']['all'] = 0;
            foreach ($data['earnings_underpaid'] as $key => $value) {
                if (!in_array($key, self::EXCLUDE)) {
                    $data['earnings_underpaid'][$key] = $value / 2;
                    $data['earnings_underpaid']['all'] += $value / 2;
                }
            }
            // keep all earnings_overpaid, taxes, deductions, pera_deductions
        } else if ($type == self::REPORT_TYPE_SECOND) {
            $data['earnings']['all'] = 0;
            foreach ($data['earnings'] as $key => $value) {
                if (in_array($key, self::EXCLUDE)) {
                    // do nothing
                } elseif ($key == \App\Adjustment::CONST_BASIC_PAY) {
                    $data['earnings'][$key] = $data['basic_gross_2'];
                    $data['earnings']['all'] += $data['basic_gross_2'];
                } else {
                    $data['earnings'][$key] = $value / 2;
                    $data['earnings']['all'] += $value / 2;
                }
            }
            $data['earnings_underpaid']['all'] = 0;
            foreach ($data['earnings_underpaid'] as $key => $value) {
                if (!in_array($key, self::EXCLUDE)) {
                    $data['earnings_underpaid'][$key] = $value / 2;
                    $data['earnings_underpaid']['all'] += $value / 2;
                }
            }

            // zero all earnings_overpaid, taxes, deductions, pera_deductions
            $data['earnings_overpaid']['all'] = 0;
            foreach ($data['earnings_overpaid'] as $key => $value) {
                if (!in_array($key, self::EXCLUDE)) {
                    $data['earnings_overpaid'][$key] = 0;
                }
            }
            $data['taxes']['all'] = 0;
            foreach ($data['taxes'] as $key => $value) {
                if (!in_array($key, self::EXCLUDE)) {
                    $data['taxes'][$key] = 0;
                }
            }
            $data['deductions']['all'] = 0;
            foreach ($data['deductions'] as $key => $value) {
                if (!in_array($key, self::EXCLUDE)) {
                    $data['deductions'][$key] = 0;
                }
            }
            $data['pera_deductions']['all'] = 0;
            foreach ($data['pera_deductions'] as $key => $value) {
                if (!in_array($key, self::EXCLUDE)) {
                    $data['pera_deductions'][$key] = 0;
                }
            }
        }

        // add net_amount and other computations
        $data['net_amount'] = $data['earnings']['all'] + $data['earnings_underpaid']['all']
            - $data['deductions']['all'] - $data['taxes']['all'] - $data['earnings_overpaid']['all'] - $data['pera_deductions']['all'];

        return $data;
    }

    private function addUniqueTitles($data, $array)
    {
        foreach ($array as $item) {
            if (!in_array($item['title'], $data)) {
                // Do not include if zero valued, except those which are forced.
                if ($item['amount'] != 0) {
                    array_push($data, $item['title']);
                }
            }
        }
        return $data;
    }

    private function addToTotal($data, $array)
    {
        foreach ($array as $item) {
            if (array_key_exists($item['title'], $data)) {
                $data[$item['title']] += $item['amount'];
            } else {
                $data[$item['title']] = $item['amount'];
            }
            // 'all'
            $data['all'] += $item['amount'];
        }
        return $data;
    }

    private function performComputationOnPaystructure($data)
    {
        // performs the computations needed for each employee (has same below but different breakdowns)
        foreach ($data as &$item) {
            $item['total_gross'] = $this->_sum_values($item['earnings']) +
                $this->_sum_values($item['reimbursements']) +
                $this->_sum_values($item['earnings_underpaid']) +
                $this->_sum_values($item['contributions_refund']) +
                $this->_sum_values($item['loans_refund']);
            $item['total_deductions'] = $this->_sum_values($item['contributions']) +
                $this->_sum_values($item['deductions']) +
                $this->_sum_values($item['loans']) +
                $this->_sum_values($item['taxes']) +
                $this->_sum_values($item['contributions_arrear']) +
                $this->_sum_values($item['earnings_overpaid']);
            $item['net_pay'] = $item['total_gross'] - $item['total_deductions'];
            $item['gross_1'] = round(($item['net_pay'] / 2) + $item['total_deductions'], 2);
            $item['gross_2'] = $item['total_gross'] - $item['gross_1'];
            $item['net_1'] = $item['gross_1'] - round($item['total_deductions'], 2);
            $item['net_2'] = $item['gross_2'];

            // compute compute basic pay
            if (sizeof($this->pickFromArray($item['earnings'], [\App\Adjustment::CONST_BASIC_PAY])) > 0) {
                $item['basic_gross_1'] = $item['gross_1'] -
                    ($this->_sum_values($this->removeFromArray($item['earnings'], [\App\Adjustment::CONST_BASIC_PAY])) / 2 +
                        $this->_sum_values($item['reimbursements']) / 2 +
                        $this->_sum_values($item['earnings_underpaid']) / 2 +
                        $this->_sum_values($item['contributions_refund']) / 2 +
                        $this->_sum_values($item['loans_refund']) / 2);
                $item['basic_gross_1'] = round($item['basic_gross_1'], 2);
                $item['basic_gross_2'] = $this->_sum_values($this->pickFromArray($item['earnings'], [\App\Adjustment::CONST_BASIC_PAY]))
                    - $item['basic_gross_1'];
            } else {
                $item['basic_gross_1'] = 0;
                $item['basic_gross_2'] = 0;
            }
        }
        unset($item);

        return $data;
    }

    private function removeFromArray($array, $keys)
    {
        $data = [];
        foreach ($array as $item) {
            if (!in_array($item['title'], $keys)) {
                array_push($data, $item);
            }
        }
        return $data;
    }

    private function pickFromArray($array, $keys)
    {
        $data = [];
        foreach ($array as $item) {
            if (in_array($item['title'], $keys)) {
                array_push($data, $item);
            }
        }
        return $data;
    }

    // ==================
    public function combine_and_filter_payruns($payruns, $employee_id = null, $is_payslip = false)
    {
        $data = array();
        foreach ($payruns as $payrun) {
            $pay_structure = $this->array_to_dict(json_decode(json_encode($payrun->pay_structure), true));
            foreach ($pay_structure as $key => $value) {
                if ($employee_id != null && $value['employee_id'] != $employee_id) {
                    continue; // Skip if not the target employee
                }
                if (array_key_exists($key, $data)) {
                    // merge the needed fields
                    $data[$key]['gross_pay'] += $value['gross_pay'];
                    // $data[$key]['net_pay'] += $value['net_pay'];
                    $data[$key]['total_deduction'] += $value['total_deduction'];
                    $data[$key]['earnings'] = array_merge($data[$key]['earnings'], $value['earnings']);
                    $data[$key]['deductions'] = array_merge($data[$key]['deductions'], $value['deductions']);
                    $data[$key]['contributions'] = array_merge($data[$key]['contributions'], $value['contributions']);
                    $data[$key]['loans'] = array_merge($data[$key]['loans'], $value['loans']);
                    $data[$key]['reimbursements'] = array_merge($data[$key]['reimbursements'], $value['reimbursements']);
                    if ($is_payslip) {
                        $data[$key]['deductions'] = array_merge($data[$key]['deductions'], $value['taxes']);
                    } else {
                        $data[$key]['taxes'] = array_merge($data[$key]['taxes'], $value['taxes']);
                    }

                    // indirect values
                    $data[$key]['earnings'] = array_merge($data[$key]['earnings'], $this->add_prefix($value['earnings_underpaid'], \App\Adjustment::PREFIX_UNDERPAID));
                    $data[$key]['earnings'] = array_merge($data[$key]['earnings'], $this->add_prefix($value['contributions_refund'], \App\Adjustment::PREFIX_REFUND));
                    $data[$key]['earnings'] = array_merge($data[$key]['earnings'], $this->add_prefix($value['loans_refund'], \App\Adjustment::PREFIX_REFUND));
                    $data[$key]['deductions'] = array_merge($data[$key]['deductions'], $this->add_prefix($value['earnings_overpaid'], \App\Adjustment::PREFIX_OVERPAID));
                    $data[$key]['contributions'] = array_merge($data[$key]['contributions'], $this->add_prefix($value['contributions_arrear'], \App\Adjustment::PREFIX_ARREAR));
                } else {
                    $data[$key] = $value;
                    if ($is_payslip) {
                        $data[$key]['deductions'] = array_merge($data[$key]['deductions'], $value['taxes']);
                        $data[$key]['taxes'] = []; // empty the taxes array for payslip
                    }
                    $data[$key]['earnings'] = array_merge($data[$key]['earnings'], $this->add_prefix($value['earnings_underpaid'], \App\Adjustment::PREFIX_UNDERPAID));
                    $data[$key]['earnings'] = array_merge($data[$key]['earnings'], $this->add_prefix($value['contributions_refund'], \App\Adjustment::PREFIX_REFUND));
                    $data[$key]['earnings'] = array_merge($data[$key]['earnings'], $this->add_prefix($value['loans_refund'], \App\Adjustment::PREFIX_REFUND));
                    $data[$key]['deductions'] = array_merge($data[$key]['deductions'], $this->add_prefix($value['earnings_overpaid'], \App\Adjustment::PREFIX_OVERPAID));
                    $data[$key]['contributions'] = array_merge($data[$key]['contributions'], $this->add_prefix($value['contributions_arrear'], \App\Adjustment::PREFIX_ARREAR));
                }

            }
        }
        $data = $this->dict_to_array($data);

        foreach ($data as &$item) {
            $item['earnings'] = $this->combine_duplicates($item['earnings']);
            $item['reimbursements'] = $this->combine_duplicates($item['reimbursements']);
            $item['contributions'] = $this->combine_duplicates($item['contributions']);
            $item['loans'] = $this->combine_duplicates($item['loans']);
            $item['deductions'] = $this->combine_duplicates($item['deductions']);
            $item['taxes'] = $this->combine_duplicates($item['taxes']);

        }
        unset($item);

        // computations (TODO:reuse method above)
        foreach ($data as &$item) {
            $item['total_gross'] = $this->_sum_values($item['earnings']) +
                $this->_sum_values($item['reimbursements']);
            $item['total_deductions'] = $this->_sum_values($item['contributions']) +
                $this->_sum_values($item['deductions']) +
                $this->_sum_values($item['loans']) +
                $this->_sum_values($item['taxes']);
            $item['net_pay'] = $item['total_gross'] - $item['total_deductions'];
            $item['gross_1'] = round(($item['net_pay'] / 2) + $item['total_deductions'], 2);
            $item['gross_2'] = $item['total_gross'] - $item['gross_1'];
            $item['net_1'] = $item['gross_1'] - round($item['total_deductions'], 2);
            $item['net_2'] = $item['gross_2'];

            // compute compute basic pay
            if (sizeof($this->pickFromArray($item['earnings'], [\App\Adjustment::CONST_BASIC_PAY])) > 0) {
                $item['basic_gross_1'] = $item['gross_1'] -
                    ($this->_sum_values($this->removeFromArray($item['earnings'], [\App\Adjustment::CONST_BASIC_PAY])) / 2 +
                        $this->_sum_values($item['reimbursements']) / 2);
                $item['basic_gross_1'] = round($item['basic_gross_1'], 2);
                $item['basic_gross_2'] = $this->_sum_values($this->pickFromArray($item['earnings'], [\App\Adjustment::CONST_BASIC_PAY]))
                    - $item['basic_gross_1'];
            } else {
                $item['basic_gross_1'] = 0;
                $item['basic_gross_2'] = 0;
            }

            // compute pera_1/2
            $pera_picked = $this->pickFromArray($item['earnings'], [\App\Adjustment::CONST_PERA_ALLOWANCE]);
            if (sizeof($pera_picked) > 0) {
                $item['pera_1'] = $pera_picked[0]['amount']/2;
                $item['pera_2'] = $pera_picked[0]['amount']/2;
            }
        }
        unset($item);

        return $data;
    }

    public function add_prefix($array, $prefix)
    {
        for ($i = 0; $i < sizeof($array); $i++) {
            if (isset($array[$i]['short_name'])) {
                $array[$i]['short_name'] = \App\Adjustment::PREFIX_SHORT_NAME[$prefix] . ' ' . $array[$i]['short_name'];
            }
            else {
                $array[$i]['short_name'] = \App\Adjustment::PREFIX_SHORT_NAME[$prefix] . ' ' . $array[$i]['title'];
            }
            $array[$i]['title'] = $prefix . ' ' . $array[$i]['title'];
        }
        return $array;
    }

    public function getTotal($paymentObj)
    {
        $totalAmnt = 0;
        foreach ($paymentObj as $payment) {
            $totalAmnt = $totalAmnt + $payment->amount;
        }
        return $totalAmnt;
    }

    public function combine_duplicates($arr)
    {
        $result = array();
        foreach ($arr as $item) {
            // ignore zero values
            if ($item['amount'] == 0) {
                continue;
            }
            if (isset($result[$item['title']])) {
                $result[$item['title']]['amount'] = $result[$item['title']]['amount'] + $item['amount'];
            } else {
                $result[$item['title']]['amount'] = $item['amount'];
                if (isset($item['short_name'])) {
                    $result[$item['title']]['short_name'] = $item['short_name'];
                }
                else {
                    $result[$item['title']]['short_name'] = null;
                }
            }
        }
        $final_result = array();
        foreach ($result as $key => $value) {
            array_push($final_result, array('title' => $key, 'amount' => $value['amount'], 'short_name' => $value['short_name']));
        }
        return $final_result;

    }


    public function numberTowords($data)
    {
        $num = $data;
        $ones = array(
            0 => " ",
            1 => "One",
            2 => "Two",
            3 => "Three",
            4 => "Four",
            5 => "Five",
            6 => "Six",
            7 => "Seven",
            8 => "Eight",
            9 => "Nine",
            10 => "Ten",
            11 => "Eleven",
            12 => "Twelve",
            13 => "Thirteen",
            14 => "Fourteen",
            15 => "Fifteen",
            16 => "Sixteen",
            17 => "Seventeen",
            18 => "Eighteen",
            19 => "Nineteen"
        );
        $tens = array(
            0 => " ",
            1 => "Ten",
            2 => "Twenty",
            3 => "Thirty",
            4 => "Forty",
            5 => "Fifty",
            6 => "Sixty",
            7 => "Seventy",
            8 => "Eighty",
            9 => "Ninety"
        );
        $hundreds = array(
            "Hundred",
            "Thousand",
            "Million",
            "Billion",
            "Trillion",
            "Quadrillion"
        );
        if ($num < 0) {
            $num = abs($num);
        };
        $num = number_format($num, 2, ".", ",");
        $num_arr = explode(".", $num);
        $wholenum = $num_arr[0];
        $decnum = $num_arr[1];
        $whole_arr = array_reverse(explode(",", $wholenum));
        krsort($whole_arr);
        $rettxt = "";
        foreach ($whole_arr as $key => $i) {
            $i = intval($i);
            if ($i < 20) {
                $rettxt .= $ones[$i];
            } elseif ($i < 100) {
                $rettxt .= $tens[substr($i, 0, 1)];
                $rettxt .= " " . $ones[substr($i, 1, 1)];
            } else {
                $rettxt .= $ones[substr($i, 0, 1)] . " " . $hundreds[0];
                $rettxt .= " " . $tens[substr($i, 1, 1)];
                $rettxt .= " " . $ones[substr($i, 2, 1)];
            }
            if ($key > 0) {
                $rettxt .= " " . $hundreds[$key] . " ";
            }
        }
        if ($decnum > 0) {
            $rettxt .= " and " . $decnum . '/100';
        }
        if ($data < 0) {
            $rettxt = 'Negative ' . $rettxt;
        };
        return $rettxt;
    }

    public function title_dict_to_array($dict)
    {
        $result = array();
        foreach ($dict as $key => $value) {
            array_push($result, array('title' => $key, 'amount' => $value));
        }
        return $result;
    }

    public function array_to_dict($array)
    {
        $converted = array();
        foreach ($array as $item) {
            $converted[$item['employee_id']] = $item;
        }
        return $converted;
    }

    public function array_to_object($array)
    {
        return json_decode(json_encode($array, True));
    }

    public function dict_to_array($dict)
    {
        $result = array();
        foreach ($dict as $key => $value) {
            array_push($result, $value);
        }
        return $result;
    }

    public function _add_unique_titles_to_result($arr, $res)
    {
        foreach ($arr as $value) {
            if (!in_array($value->title, $res)) array_push($res, $value->title);
        }
        return $res;
    }

    public function _write_keys($worksheet, $row, $col, $arr, $prefix)
    {
        foreach ($arr as $key) {
            if ($prefix == '') {
                $value = $key;
            } else {
                $value = $prefix . ' ' . $key;
            }
            $worksheet->setCellValueByColumnAndRow($col, $row, $value);
            $col++;
        }
        return $col;
    }

    public function _write_values($worksheet, $row, $col, $keys, $items)
    {
        $items_dict = array();
        foreach ($items as $item) {
            $items_dict[$item->title] = $item->amount;
        }
        foreach ($keys as $key) {
            $worksheet->getStyleByColumnAndRow($col, $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->setCellValueByColumnAndRow($col, $row, isset($items_dict[$key]) ? $items_dict[$key] : 0);
            $col++;
        }
        return $col;
    }

    public function _sum_values($items)
    {
        $result = 0;
        foreach ($items as $item) {
            $result = $result + ((object) $item)->amount;
        }
        return $result;
    }

    public function _total_by_title($total, $values)
    {

        foreach ($values as $value) {
            if (!isset($total[$value->title])) {
                $total[$value->title] = $value->amount;
            } else {
                $total[$value->title] = $total[$value->title] + $value->amount;
            }
        }
        return $total;
    }

    public function get_middle_of_month($date)
    {
        return $date->copy()->firstOfMonth()->addDays(14);
    }

    public function get_start_end_from_payrun($payrun, $type)
    {
        $payroll_period_start = Carbon::parse($payrun->payroll_period_start);
        $payroll_period_end = Carbon::parse($payrun->payroll_period_end);
        if ($type == self::REPORT_TYPE_FIRST) {
            $payroll_period_end = $this->get_middle_of_month($payroll_period_start);
        } elseif ($type == self::REPORT_TYPE_SECOND) {
            $payroll_period_start = $this->get_middle_of_month($payroll_period_start)->addDays(1);
        }
        return [$payroll_period_start, $payroll_period_end];
    }

    public function getShortNameFromTitle($title, $lookup) {
        // strip prefix if existing

        // lookup

        // add back prefix
    }

    public function getSheetsFromPayrun($payrun) {
        return ceil(sizeof($payrun->pay_structure)/\App\Http\Controllers\Reports\PayrollRegistryController::NUM_PER_PAGE);
    }
}
