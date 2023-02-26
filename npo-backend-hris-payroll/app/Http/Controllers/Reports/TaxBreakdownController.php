<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use PDF;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Log;

class TaxBreakdownController extends Controller
{
    public function get(Request $request)
    {
        $unauthorized = false;
        if ($unauthorized) {
            return $unauthorized;
        }
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();

        $adjustments = \App\Adjustment::all();
        $adjustment_lookup = $adjustments->keyBy('id');

        $data = [];
        // iterate through months of the year from the logs
        $current_start = Carbon::createFromFormat('Y-m-d', request('year') . '-01-01');
        $end = Carbon::createFromFormat('Y-m-d', request('year') . '-01-01')->addYear()->firstOfMonth();

        while ($current_start < $end) {
            $current_end = Carbon::createFromFormat('Y-m-d', $current_start->format('Y-m-d'))
                ->addMonth()->firstOfMonth();

            $payruns = \App\Payrun::where('payroll_date', '>=', $current_start->format('Y-m-d'))
                ->where('payroll_date', '<', $current_end->format('Y-m-d'))
                ->where('status', \App\Payrun::PAYRUN_STATUS_COMPLETED)
                ->get();
            $payrun_data = $util->combine_and_filter_payruns($payruns, request('employee_id'), true);

            // Log::debug($payrun_data);
            $payrun_data = sizeof($payrun_data)>0 ? $payrun_data[0] : null; // 0 since only 1 employee
            if ($payrun_data) {
                $payrun_data['earnings'] = $util->combine_duplicates($payrun_data['earnings']);
                $payrun_data['contributions'] = $util->combine_duplicates($payrun_data['contributions']);
                $payrun_data['deductions'] = $util->combine_duplicates($payrun_data['deductions']);
                $payrun_data['taxes'] = $util->combine_duplicates($payrun_data['taxes']);
            }
            // separate taxable vs. non-taxable earnings
            $taxable = [];
            $non_taxable = [];
            foreach($payrun_data['earnings'] as $earning) {
                if (!isset($earning['id']) || $earning['title'] == \App\Adjustment::CONST_BASIC_PAY) {
                    array_push($taxable, $earning);
                }
                else if ($adjustment_lookup[$earning['id']]['tax'] == \App\Adjustment::CONST_TAXABLE) {
                    array_push($taxable, $earning);
                }
                else {
                    array_push($non_taxable, $earning);
                }
            }

            $payrun_data['earnings'] = $taxable;
            $data[] = array(
                "id" => $current_start->format('F Y'),
                "month" => $current_start->format('m'),
                "payrun_data" => $payrun_data,
                "non_taxable" => $non_taxable
            );
            $current_start = $current_end;
        }

        // - get the latest regular payroll to get the projected values from "tax_computation"
        // - form projection array earnings, contributions, taxes
        // - monthly_basic_tax
        // - basic_pay
        // - contributions (handle Pag-Ibig...)
        $latest_basic_pay = \App\PayrollEmployeeLog::where('year', '=', request('year'))
            ->where('employee_id', '=', request('employee_id'))
            ->where('type_of_string', '=', \App\Adjustment::CONST_BASIC_PAY)
            ->orderBy('month', 'DESC')->first();

        // TODO: return error if no basic pay for the current year yet
        if (!$latest_basic_pay) {

        }

        // TODO: make projection
        $start_projected_month = $latest_basic_pay->month + 1;
        $latest_payrun = \App\Payrun::where('id', '=', $latest_basic_pay->payroll_id)->first();
        $pay_structure = $this->array_to_dict(json_decode(json_encode($latest_payrun->pay_structure), true));

        $current_start = Carbon::createFromFormat('Y-m-d', request('year') . '-' . $start_projected_month . '-01');
        while ($current_start < $end) {
            $current_end = Carbon::createFromFormat('Y-m-d', $current_start->format('Y-m-d'))
                ->addMonth()->firstOfMonth();


            $current_start = $current_end;
        }

        // TODO: Print the output to file (similar to how PayrollCard was done)


    }

    private function BoxStyle()
    {
        return [
            'font' => [
                'size' => 10,
                'bold' => false
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
    }
    private function headerItems() {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => array('argb' => 'ffa2c4c9')
            ]
        ];
    }

    private function headerTotals() {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => array('argb' => 'fffff2cc')
            ]
        ];
    }

    private function headerNet() {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => array('argb' => 'ffffd966')
            ]
        ];
    }

    private function footerTotal() {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => array('argb' => 'ffcfe2f3')
            ]
        ];

    }

    private function centerBold() {
        return [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
    }



}
