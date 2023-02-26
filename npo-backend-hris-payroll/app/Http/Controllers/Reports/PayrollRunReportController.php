<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class PayrollRunReportController extends Controller
{
    public function getOvertimeReport(Request $request, $id) {
        $unauthorized = $this->is_not_authorized(['payroll_registry']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($id) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/Payroll/ot_report.xlsx';
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setLoadSheetsOnly('Sheet1');
            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();

            $this->generateOt($id, $worksheet);

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'overtime_report' . '.xlsx"');

        return $streamedResponse;
    }

    public function getSimulatedSummary(Request $request, $id) {
        $unauthorized = $this->is_not_authorized(['payroll_registry']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($id) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/Payroll/empty.xlsx';
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setLoadSheetsOnly('Sheet1');
            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();

            $this->generateMasterList($id, $worksheet);

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'master_list' . '.xlsx"');

        return $streamedResponse;
    }

    public function generateOt($id, $worksheet) {
        $payrun = \App\Payrun::where('id', $id)->first();
        $employees = json_decode(json_encode($payrun->pay_structure, True));

        // form header based on deduction_start and deduction_end
        $current_start = Carbon::createFromFormat('Y-m-d H:i:s', $payrun->deduction_start);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $payrun->deduction_end);
        if ($current_start->format('M') == $end->format('M')) {
            $dateString = $current_start->format('F d') . ' - ' . $end->format('d, Y');
        }

        // form totals breakdown
        $time_data_types = ['Regular'];
        foreach ($employees as $employee) {
            foreach($employee->overtime_requests as $overtime_request) {
                if (!in_array($overtime_request->date_type, $time_data_types)) {
                    array_push($time_data_types, $overtime_request->date_type);
                }
            }
        }

        $keys = [];
        $print_keys = [];
        $print_days = [];
        $totals = [];
        $end->addDays(1);
        while ($current_start->toDateString() != $end->toDateString()) {
            $current_end = Carbon::createFromFormat('Y-m-d', $current_start->format('Y-m-d'))->addDay();
            $keys[] = $current_start->format('Y-m-d');
            $print_keys[] = $current_start->format('d');
            $print_days[] = $current_start->isoFormat('ddd');
            $totals[$current_start->format('Y-m-d')] = 0;
            $current_start = $current_end;
        }

        // print the headers to the file
        $worksheet->getColumnDimensionByColumn(1)->setWidth('5', 'pt');
        $worksheet->getColumnDimensionByColumn(2)->setWidth('30', 'pt');
        $worksheet->getColumnDimensionByColumn(3)->setWidth('30', 'pt');
        $worksheet->getColumnDimensionByColumn(4)->setWidth('15', 'pt');

        $row=1; $col=1;
        $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->centerBold());
        $worksheet->mergeCellsByColumnAndRow($col,$row,$col+1+sizeof($keys),$row);
        $worksheet->setCellValueByColumnAndRow($col, $row, 'NATIONAL PRINTING OFFICE');
        $row++;
        $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->centerBold());
        $worksheet->mergeCellsByColumnAndRow($col,$row,$col+1+sizeof($keys),$row);
        $worksheet->setCellValueByColumnAndRow($col, $row, 'OVERTIME PAYROLL REGISTER');
        $row++;
        $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->centerBold());
        $worksheet->mergeCellsByColumnAndRow($col,$row,$col+1+sizeof($keys),$row);
        $worksheet->setCellValueByColumnAndRow($col, $row, $dateString);

        $row = 6; $col = 5;
        foreach($print_days as $key) {
            $worksheet->getColumnDimensionByColumn($col)->setWidth('8', 'pt');
            $worksheet->setCellValueByColumnAndRow($col,$row,$key);
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->centerBold());
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
            $col++;
        }
        foreach($time_data_types as $time_data_type) {
            $worksheet->setCellValueByColumnAndRow($col,$row,$time_data_type);
            $worksheet->mergeCellsByColumnAndRow($col,$row,$col,$row+1);
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->centerBold());
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
            $col++;
            $totals[$time_data_type] = 0;
        }
        $row++; $col = 1;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'No'); $col++;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Name'); $col++;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Position'); $col++;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Monthly Salary'); $col++;
        $worksheet->getStyleByColumnAndRow($col-4,$row,$col-1,$row)->applyFromArray($this->centerBold());
        $worksheet->getStyleByColumnAndRow($col-4,$row,$col-1,$row)->applyFromArray($this->BoxStyle());
        foreach($print_keys as $key) {
            $worksheet->setCellValueByColumnAndRow($col,$row,$key);
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->centerBold());
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
            $col++;
        }
        $row++; $counter = 1;
        foreach ($employees as $employee) {
            // form overtime_requests dictionary from array
            $dict = [];
            $totals_per_type = [];
            foreach ($time_data_types as $time_data_type) {
                $totals_per_type[$time_data_type] = 0;
            }
            foreach($employee->overtime_requests as $overtime_request) {
                $date = Carbon::createFromFormat('Y-m-d', $overtime_request->dtr_date)->format('Y-m-d');
                if (isset($dict[$date])) {
                    $dict[$date] += round($overtime_request->duration_in_minutes / 60, 2);
                }
                else {
                    $dict[$date] = round($overtime_request->duration_in_minutes / 60, 2);
                }

                $totals[$date] = $totals[$date] + round($overtime_request->duration_in_minutes / 60, 2);
                $totals[$overtime_request->date_type] = $totals[$overtime_request->date_type] + round($overtime_request->duration_in_minutes / 60, 2);
                $totals_per_type[$overtime_request->date_type] += round($overtime_request->duration_in_minutes / 60, 2);
            }
            $col = 1;
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
            $worksheet->setCellValueByColumnAndRow($col,$row,$counter); $col++;
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
            $worksheet->setCellValueByColumnAndRow($col,$row,$employee->last_name . ", " . $employee->first_name . " " . $employee->middle_name); $col++;
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
            $worksheet->setCellValueByColumnAndRow($col,$row,$employee->position_name); $col++;
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->setCellValueByColumnAndRow($col,$row,$employee->basic_pay); $col++;
            foreach ($keys as $key) {
                $value = isset($dict[$key]) ? $dict[$key] : 0;
                $worksheet->setCellValueByColumnAndRow($col,$row,$value);
                $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $col++;
            }
            foreach ($time_data_types as $time_data_type) {
                $worksheet->setCellValueByColumnAndRow($col,$row,$totals_per_type[$time_data_type]);
                $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->BoxStyle());
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $col++;
            }
            $row++; $counter++;
        }

        $row++; $col=4;
        $worksheet->setCellValueByColumnAndRow($col, $row, '     TOTAL');
        $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->bold());
        $col++;
        foreach ($keys as $key) {
            $worksheet->setCellValueByColumnAndRow($col,$row,$totals[$key]);
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->bold());
            $col++;
        }
        foreach ($time_data_types as $time_data_type) {
            $worksheet->setCellValueByColumnAndRow($col,$row,$totals[$time_data_type]);
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($this->bold());
            $col++;
        }
    }

    public function generateMasterList($id, $worksheet) {
        $payrun = \App\Payrun::where('id', $id)->first();
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();
        $employees = json_decode(json_encode($util->combine_and_filter_payruns([$payrun], ''), True));
        $earning_keys = [];
        $reimbursement_keys = [];
        $contribution_keys = [];
        $deduction_keys = [];
        $loan_keys = [];
        $tax_keys = [];

        foreach ($employees as $employee) {
            $earning_keys                 = $util->_add_unique_titles_to_result($employee->earnings, $earning_keys);
            $reimbursement_keys           = $util->_add_unique_titles_to_result($employee->reimbursements, $reimbursement_keys);
            $contribution_keys            = $util->_add_unique_titles_to_result($employee->contributions, $contribution_keys);
            $deduction_keys               = $util->_add_unique_titles_to_result($employee->deductions, $deduction_keys);
            $loan_keys                    = $util->_add_unique_titles_to_result($employee->loans, $loan_keys);
            $tax_keys                     = $util->_add_unique_titles_to_result($employee->taxes, $tax_keys);
        }

        // ---- populate sheet ----
        // write headers
        $row = 1;
        $worksheet->setCellValueByColumnAndRow(1,$row,'No.');
        $worksheet->getColumnDimensionByColumn(1)->setWidth('5', 'pt');
        $worksheet->setCellValueByColumnAndRow(2,$row,'Name');
        $worksheet->getColumnDimensionByColumn(2)->setWidth('30', 'pt');
        $worksheet->setCellValueByColumnAndRow(3,$row,'Division');
        $worksheet->getColumnDimensionByColumn(3)->setWidth('30', 'pt');
        $worksheet->setCellValueByColumnAndRow(4,$row,'Position / Grade');
        $worksheet->getColumnDimensionByColumn(4)->setWidth('30', 'pt');
        $worksheet->setCellValueByColumnAndRow(5,$row,'Monthly Salary');
        $worksheet->getColumnDimensionByColumn(5)->setWidth('12', 'pt');
        $col = 6;
        $col = $util->_write_keys($worksheet, $row, $col, $earning_keys, '');
        $col = $util->_write_keys($worksheet, $row, $col, $reimbursement_keys, '');
        $col = $util->_write_keys($worksheet, $row, $col, $contribution_keys, '');
        $col = $util->_write_keys($worksheet, $row, $col, $deduction_keys, '');
        $col = $util->_write_keys($worksheet, $row, $col, $loan_keys, '');
        $col = $util->_write_keys($worksheet, $row, $col, $tax_keys, '');

        $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->headerItems());

        if ($payrun->run_type == \App\Http\Controllers\PayrollRunController::RUN_TYPE_REGULAR) {
            $worksheet->setCellValueByColumnAndRow($col,$row,'15th Gross Basic'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'15th Gross PERA'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'15th Gross Total'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'15th Net'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'End Gross Basic'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'End Gross PERA'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'End Gross Total'); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,'End Net'); $col++;
            $worksheet->getStyleByColumnAndRow($col-8,$row,$col-1,$row)->applyFromArray($this->headerTotals());
        }

        $worksheet->setCellValueByColumnAndRow($col,$row,'Total Gross'); $col++;
        $worksheet->setCellValueByColumnAndRow($col,$row,'Total Deductions'); $col++;
        $worksheet->setCellValueByColumnAndRow($col,$row,'Net Pay'); $col++;
        $worksheet->getStyleByColumnAndRow($col-3,$row,$col-2,$row)->applyFromArray($this->headerTotals());
        $worksheet->getStyleByColumnAndRow($col-1,$row,$col-1,$row)->applyFromArray($this->headerNet());
        for ($i=6; $i<$col; $i++) {
            $worksheet->getColumnDimensionByColumn($i)->setWidth('12', 'pt');
        }
        $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->BoxStyle());

        // write the entries
        $row = 2;
        $no = 0;
        foreach ($employees as $employee) {
            $no = $no + 1;
            $worksheet->setCellValueByColumnAndRow(1,$row,$no);
            $worksheet->setCellValueByColumnAndRow(2,$row,$employee->last_name . ", " . $employee->first_name . " " . $employee->middle_name);
            $worksheet->setCellValueByColumnAndRow(3,$row,$employee->department_name);
            $worksheet->setCellValueByColumnAndRow(4,$row,$employee->position_name . '/ SG' . $employee->salary_grade_id);

            $worksheet->getStyleByColumnAndRow(5,$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->setCellValueByColumnAndRow(5,$row,$employee->basic_pay);
            $col = 6;
            $col = $util->_write_values($worksheet, $row, $col, $earning_keys, $employee->earnings);
            $col = $util->_write_values($worksheet, $row, $col, $reimbursement_keys, $employee->reimbursements);
            $col = $util->_write_values($worksheet, $row, $col, $contribution_keys, $employee->contributions);
            $col = $util->_write_values($worksheet, $row, $col, $deduction_keys, $employee->deductions);
            $col = $util->_write_values($worksheet, $row, $col, $loan_keys, $employee->loans);
            $col = $util->_write_values($worksheet, $row, $col, $tax_keys, $employee->taxes);


            if ($payrun->run_type == \App\Http\Controllers\PayrollRunController::RUN_TYPE_REGULAR) {
                $worksheet->setCellValueByColumnAndRow($col,$row,$employee->basic_gross_1); $col++;
                $worksheet->setCellValueByColumnAndRow($col,$row,$employee->pera_1); $col++;
                $worksheet->setCellValueByColumnAndRow($col,$row,$employee->gross_1); $col++;
                $worksheet->setCellValueByColumnAndRow($col,$row,$employee->net_1); $col++;
                $worksheet->setCellValueByColumnAndRow($col,$row,$employee->basic_gross_2); $col++;
                $worksheet->setCellValueByColumnAndRow($col,$row,$employee->pera_2); $col++;
                $worksheet->setCellValueByColumnAndRow($col,$row,$employee->gross_2); $col++;
                $worksheet->setCellValueByColumnAndRow($col,$row,$employee->net_2); $col++;
                $worksheet->getStyleByColumnAndRow($col-8,$row,$col-1,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->getStyleByColumnAndRow($col-8,$row,$col-1,$row)->applyFromArray($this->headerTotals());
            }

            $worksheet->setCellValueByColumnAndRow($col,$row,$employee->total_gross); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,$employee->total_deductions); $col++;
            $worksheet->setCellValueByColumnAndRow($col,$row,$employee->net_pay); $col++;
            $worksheet->getStyleByColumnAndRow($col-3,$row,$col-1,$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyleByColumnAndRow($col-3,$row,$col-2,$row)->applyFromArray($this->headerTotals());
            $worksheet->getStyleByColumnAndRow($col-1,$row,$col-1,$row)->applyFromArray($this->headerNet());
            $worksheet->getStyleByColumnAndRow(1,$row,$col-1,$row)->applyFromArray($this->BoxStyle());

            $row++;
        }
    }

    private function headerItems() {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => array('argb' => 'ffcfe2f3')
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

    private function BoxStyle()
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
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

    private function bold() {
        return [
            'font' => [
                'bold' => true,
            ]
        ];
    }

}
