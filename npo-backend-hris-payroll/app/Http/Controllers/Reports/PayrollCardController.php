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

class PayrollCardController extends Controller
{
    public function get(Request $request)
    {
        //$unauthorized = $this->is_not_authorized(['nosi']);
        $unauthorized = false;
        if ($unauthorized) {
            return $unauthorized;
        }
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();

        $data = [];
        // iterate through months of the year and get completed payroll information
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
                $payrun_data['reimbursements'] = $util->combine_duplicates($payrun_data['reimbursements']);
                $payrun_data['contributions'] = $util->combine_duplicates($payrun_data['contributions']);
                $payrun_data['deductions'] = $util->combine_duplicates($payrun_data['deductions']);
                $payrun_data['loans'] = $util->combine_duplicates($payrun_data['loans']);
                $payrun_data['taxes'] = $util->combine_duplicates($payrun_data['taxes']);
            }
            $data[] = array(
                "id" => $current_start->format('F Y'),
                "payrun_data" => $payrun_data
            );
            $current_start = $current_end;
        }

        $card = array(
            'employee' => \App\Employee::where('id', request('employee_id'))->first(),
            'data' => $data
        );

        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($card) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/Payroll/empty.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $reader->setLoadSheetsOnly('Sheet1');

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = json_decode(json_encode($card['data'], True));
            $earning_keys = [];
            $reimbursement_keys = [];
            $contribution_keys = [];
            $deduction_keys = [];
            $loan_keys = [];
            $tax_keys = [];

            $earnings_total = array();
            $reimbursements_total = array();
            $contributions_total = array();
            $deductions_total = array();
            $loans_total = array();
            $taxes_total = array();

            $util = new \App\Http\Controllers\Reports\PayrollUtilController();
            foreach ($data as $datum) {
                $item = $datum->payrun_data;
                if ($item!=null) {

                    $earning_keys = $util->_add_unique_titles_to_result($item->earnings, $earning_keys);
                    $reimbursement_keys = $util->_add_unique_titles_to_result($item->reimbursements, $reimbursement_keys);
                    $contribution_keys = $util->_add_unique_titles_to_result($item->contributions, $contribution_keys);
                    $deduction_keys = $util->_add_unique_titles_to_result($item->deductions, $deduction_keys);
                    $loan_keys = $util->_add_unique_titles_to_result($item->loans, $loan_keys);
                    $tax_keys = $util->_add_unique_titles_to_result($item->taxes, $tax_keys);

                    $earnings_total = $util->_total_by_title($earnings_total, $item->earnings);
                    $reimbursements_total = $util->_total_by_title($reimbursements_total, $item->reimbursements);
                    $contributions_total = $util->_total_by_title($contributions_total, $item->contributions);
                    $deductions_total = $util->_total_by_title($deductions_total, $item->deductions);
                    $loans_total = $util->_total_by_title($loans_total, $item->loans);
                    $taxes_total = $util->_total_by_title($taxes_total, $item->taxes);
                }
            }

            // ---- populate sheet ----
            // write headers
            $worksheet->setCellValueByColumnAndRow(1,1,'Name:');
            $worksheet->setCellValueByColumnAndRow(2,1,$card['employee']->name);

            $row = 3;
            $worksheet->getRowDimension($row)->setRowHeight('40');
            $worksheet->setCellValueByColumnAndRow(1,$row,'Period Covered');
            $worksheet->getColumnDimensionByColumn(1)->setWidth('20', 'pt');
            $worksheet->setCellValueByColumnAndRow(2,$row,'Division');
            $worksheet->getColumnDimensionByColumn(2)->setWidth('30', 'pt');
            $worksheet->setCellValueByColumnAndRow(3,$row,'Position / Grade');
            $worksheet->getColumnDimensionByColumn(3)->setWidth('30', 'pt');
            $worksheet->setCellValueByColumnAndRow(4,$row,'Monthly Salary');
            $worksheet->getColumnDimensionByColumn(4)->setWidth('12', 'pt');
            $col=5;
            $col = $util->_write_keys($worksheet, $row, $col, $earning_keys, '');
            $col = $util->_write_keys($worksheet, $row, $col, $reimbursement_keys, '');
            $col = $util->_write_keys($worksheet, $row, $col, $contribution_keys, '');
            $col = $util->_write_keys($worksheet, $row, $col, $deduction_keys, '');
            $col = $util->_write_keys($worksheet, $row, $col, $loan_keys, '');
            $col = $util->_write_keys($worksheet, $row, $col, $tax_keys, '');

            $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->headerItems());

            $worksheet->setCellValueByColumnAndRow($col,$row,'Total Gross'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'Total Deductions'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'Net Pay'); $col++;
            $worksheet->getStyleByColumnAndRow($col-3,$row,$col-2,$row)->applyFromArray($this->headerTotals());
            $worksheet->getStyleByColumnAndRow($col-1,$row,$col-1,$row)->applyFromArray($this->headerNet());

            for ($i=5; $i<$col; $i++) {
                $worksheet->getColumnDimensionByColumn($i)->setWidth('12', 'pt');
            }
            $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->BoxStyle());
            $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->centerBold());
            $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->getAlignment()->setWrapText(true);

            // write the entries
            $row = 4;
            $employee = $card['employee'];
            foreach ($data as $datum) {
                $item = $datum->payrun_data;
                if ($item!=null) {
                    $sg = $item->salary_grade_id ? '/ SG' . $item->salary_grade_id : '';
                    $worksheet->setCellValueByColumnAndRow(1,$row,$datum->id);
                    $worksheet->setCellValueByColumnAndRow(2,$row,$item->department_name);
                    $worksheet->setCellValueByColumnAndRow(3,$row,$item->position_name . $sg);
                    $worksheet->getStyleByColumnAndRow(4,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $worksheet->setCellValueByColumnAndRow(4,$row,$item->basic_pay);
                    $col = 5;
                    $col = $util->_write_values($worksheet, $row, $col, $earning_keys, $item->earnings);
                    $col = $util->_write_values($worksheet, $row, $col, $reimbursement_keys, $item->reimbursements);
                    $col = $util->_write_values($worksheet, $row, $col, $contribution_keys, $item->contributions);
                    $col = $util->_write_values($worksheet, $row, $col, $deduction_keys, $item->deductions);
                    $col = $util->_write_values($worksheet, $row, $col, $loan_keys, $item->loans);
                    $col = $util->_write_values($worksheet, $row, $col, $tax_keys, $item->taxes);

                    // totals
                    $worksheet->setCellValueByColumnAndRow($col,$row,$item->total_gross); $col++;
                    $worksheet->setCellValueByColumnAndRow($col,$row,$item->total_deductions); $col++;
                    $worksheet->setCellValueByColumnAndRow($col,$row,$item->net_pay); $col++;
                    $worksheet->getStyleByColumnAndRow($col-3,$row,$col-1,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $worksheet->getStyleByColumnAndRow($col-3,$row,$col-2,$row)->applyFromArray($this->headerTotals());
                    $worksheet->getStyleByColumnAndRow($col-1,$row,$col-1,$row)->applyFromArray($this->headerNet());

                    $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->BoxStyle());
                    $row++;
                }
            }

            // print totals
            $row = 20;
            $worksheet->getRowDimension($row)->setRowHeight('40');
            $worksheet->setCellValueByColumnAndRow(1,$row,'TOTAL');
            $col = 5;
            $item = (object) array(
                'earnings' => json_decode(json_encode($util->title_dict_to_array($earnings_total),true)),
                'reimbursements' => json_decode(json_encode($util->title_dict_to_array($reimbursements_total),true)),
                'contributions' => json_decode(json_encode($util->title_dict_to_array($contributions_total),true)),
                'deductions' => json_decode(json_encode($util->title_dict_to_array($deductions_total),true)),
                'loans' => json_decode(json_encode($util->title_dict_to_array($loans_total),true)),
                'taxes' => json_decode(json_encode($util->title_dict_to_array($taxes_total),true))
            );
            $col = $util->_write_values($worksheet, $row, $col, $earning_keys, $item->earnings);
            $col = $util->_write_values($worksheet, $row, $col, $reimbursement_keys, $item->reimbursements);
            $col = $util->_write_values($worksheet, $row, $col, $contribution_keys, $item->contributions);
            $col = $util->_write_values($worksheet, $row, $col, $deduction_keys, $item->deductions);
            $col = $util->_write_values($worksheet, $row, $col, $loan_keys, $item->loans);
            $total_gross =  $util->_sum_values($item->earnings) +
                $util->_sum_values($item->reimbursements);
            $total_deductions = $util->_sum_values($item->contributions) +
                $util->_sum_values($item->deductions) +
                $util->_sum_values($item->loans) +
                $util->_sum_values($item->taxes);
            $net_pay = $total_gross - $total_deductions;
            $worksheet->setCellValueByColumnAndRow($col,$row,$total_gross); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,$total_deductions); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,$net_pay); $col++;
            $worksheet->getStyleByColumnAndRow($col-3,$row,$col-1,$row)->getNumberFormat()->setFormatCode('#,##0.00');

            $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->footerTotal());
            $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->centerBold());

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'payroll_card' . '.xlsx"');

        return $streamedResponse;
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
