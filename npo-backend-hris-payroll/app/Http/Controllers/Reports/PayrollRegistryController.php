<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PayrollRegistryController extends Controller
{
    public const MIN_EARNINGS_NUM = 3;
    public const NUM_PER_PAGE = 10;

    public function registry(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['payroll_registry']);
        if ($unauthorized) {
            return $unauthorized;
        }
        // $payroll_id = $request->all();
        $payroll_id = request('id');
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();
        $registry = $this->organizeRegistryData($payroll_id, $util::REPORT_TYPE_FULL);

        $signatories = \App\Signatories::where('report_name', 'Payroll Registry')->first();

        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($registry, $signatories) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/Payroll_Registry.xlsx';
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setLoadSheetsOnly('Registry');
            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $this->generateRegistrySheet($registry, $signatories, $worksheet);

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'Payroll_Registry' . '.xlsx"');

        return $streamedResponse;
    }

    public function organizeRegistryData($payroll_id, $type)
    {
        $util = new PayrollUtilController();
        $payrun = \App\Payrun::where('id', $payroll_id)->first();
        $employeeName = \App\PersonalInformation::select('employee_id', 'first_name', 'last_name')
            ->whereIn('employee_id', $payrun->pluck('created_by')->flatten())
            ->get();
        $payrun_combined = $util->combine_and_filter_payruns([$payrun], null);

        $chunk = new \stdClass;
        $chunk->run_type = $payrun->run_type;
        $index = 0;
        $chunk->total_earnings = 0;
        $chunk->total_deductions = 0;
        $chunk->total_gross_pay = 0;
        $chunk->total_net_taxable_pay = 0;
        $chunk->total_monthly = 0;
        $chunk->count = 0;
        $chunk->entries = array();

        // individual computations based on type
        foreach ($payrun_combined as &$employee) {
            // recompute earnings, reimbursements, deductions, loans, contributions, taxes
            if ($type == PayrollUtilController::REPORT_TYPE_FIRST) {
                foreach (['earnings', 'reimbursements'] as $target) {
                    foreach($employee[$target] as $key => $value) {
                        if ($key == \App\Adjustment::CONST_BASIC_PAY) {
                            $employee[$target][$key]['amount'] = $employee['basic_gross_1'];
                        }
                        else {
                            $employee[$target][$key]['amount'] = $employee[$target][$key]['amount']/2;
                        }
                    }
                }

            }
            else if ($type == PayrollUtilController::REPORT_TYPE_SECOND) {
                foreach (['earnings', 'reimbursements'] as $target) {
                    foreach($employee[$target] as $key => $value) {
                        if ($key == \App\Adjustment::CONST_BASIC_PAY) {
                            $employee[$target][$key]['amount'] = $employee['basic_gross_2'];
                        }
                        else {
                            $employee[$target][$key]['amount'] = $employee[$target][$key]['amount']/2;
                        }
                    }
                }

                // deductions, loans, contributions, taxes set to 0
                foreach (['deductions', 'loans', 'contributions', 'taxes'] as $target) {
                    foreach($employee[$target] as $key => $value) {
                        $employee[$target][$key]['amount'] = 0;
                    }
                }

            }
        }

        unset($employee);
        $payrun_combined = json_decode(json_encode($payrun_combined, True));

        foreach ($payrun_combined as $employee) {
            $entry = $employee;
            $entry->index = $index + 1;
            $entry->name = $employee->last_name . ", " . $employee->first_name . " " . $employee->middle_name;
            $index++;
            array_push($chunk->entries, $entry);
            $chunk->total_monthly +=  $entry->basic_pay;

            if ($type == PayrollUtilController::REPORT_TYPE_FIRST) {
                $employee->total_gross = $employee->gross_1;
                $employee->net_pay = $entry->net_1;
            }
            else if ($type == PayrollUtilController::REPORT_TYPE_SECOND) {
                $employee->total_gross = $employee->gross_2;
                $employee->net_pay = $entry->net_2;
                $employee->total_deductions = 0;
            }
            $chunk->total_gross_pay += $employee->total_gross;
            $chunk->total_net_taxable_pay += $entry->net_pay;
            $chunk->total_deductions += $entry->total_deductions;
            $chunk->total_earnings += $entry->net_pay;

            $chunk->count = $entry->index;
        }

        $payroll_dates = $util->get_start_end_from_payrun($payrun, $type);
        $chunk->report_title = 'Period of ' . $payroll_dates[0]->format('F d') . ' - ' . $payroll_dates[1]->format('d, Y');

        $chunk->report_subtitle = [];
        if ($payrun->run_type !== \App\Http\Controllers\PayrollRunController::RUN_TYPE_REGULAR) {
            $chunk->report_title = $payrun->title;
            $subtitle = $payrun->subtitle;
            if ($subtitle == null) {
                $subtitle = [];
            }
            $chunk->report_subtitle = $subtitle;
        }

        $chunk->payroll_name = $payrun->payroll_name;
        $chunk->created_at = Carbon::parse($payrun->created_at)->format('F d, Y');
        if ($payrun->run_type == \App\Http\Controllers\PayrollRunController::RUN_TYPE_OFFCYCLE) {
            $chunk->report_title = $payrun->title;
            $chunk->deduction_period = $this->formatPayPeriod(
                $payrun->payroll_period_start,
                $payrun->payroll_period_end
            );
        }
        else {
            $chunk->deduction_period = $this->formatPayPeriod(
                $payrun->deduction_start,
                $payrun->deduction_end
            );
        }
        // created by
        $createdById = $payrun->created_by;
        $createdBy = $employeeName->filter(function ($item) use ($createdById) {
            return $item->employee_id == $createdById;
        })->first();
        $chunk->created_by = $createdBy ? $createdBy->first_name . ' ' . $createdBy->last_name : '';
        return $chunk;

    }

    public function generateRegistrySheet($data, $signatories, $worksheet)
    {
        $util = new PayrollUtilController();

        //signatories
        $worksheet->getCell('C20')->setValue(($signatories->signatories[0]['name'] ?? 'Name#1'));
        $worksheet->getCell('C21')->setValue(($signatories->signatories[0]['title'] ?? 'Title#1'));
        $worksheet->getCell('H20')->setValue(($signatories->signatories[1]['name'] ?? 'Name#2'));
        $worksheet->getCell('H21')->setValue(($signatories->signatories[1]['title'] ?? 'Title#2'));
        $worksheet->getCell('C30')->setValue(($signatories->signatories[2]['name'] ?? 'Name#3'));
        $worksheet->getCell('C31')->setValue(($signatories->signatories[2]['title'] ?? 'Title#3'));
        $worksheet->getCell('H30')->setValue(($signatories->signatories[3]['name'] ?? 'Name#4'));
        $worksheet->getCell('H31')->setValue(($signatories->signatories[3]['title'] ?? 'Title#4'));

        $worksheet->getPageSetup()->setFitToWidth(1);
        $worksheet->getPageSetup()->setFitToHeight(0);

        $registry_chunks = array_chunk($data->entries, self::NUM_PER_PAGE);
        $row = 1; //row start
        $inserted_row_count = $this->getDynamicRowCount($data->entries) + (self::NUM_PER_PAGE * COUNT($registry_chunks));
        $worksheet->insertNewRowBefore(
            $row + 1,
            ($inserted_row_count)
        );
        $deduc_column = [['H', 'I'], ['J', 'K',], ['L', 'M']];
        $merge_col = ['A', 'B', 'C', 'D', 'G', 'N', 'O', 'P'];

        $deduc_column_total = [['H', 'I'], ['J', 'K',], ['L', 'M']]; //page break down total column

        $index = 1;
        $border =  [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        foreach ($registry_chunks as $chunk) {

            //header
            $startRow = $row;
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
            $worksheet->mergeCells('A' . $row . ':O' . $row);
            $worksheet->getCell('A' . $row)->setValue('NATIONAL PRINTING OFFICE');
            $worksheet->getStyle('A' . $row)->getFont()->setSize(16);
            $worksheet->getRowDimension($row)->setRowHeight(24);
            $page = 'Page ' . $index . ' of ' . (COUNT($registry_chunks) + 1);
            $worksheet->getStyle('P' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $worksheet->getCell('P' . $row)->setValue($page);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFont()->setBold(true);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCFE2F3');
            $row++;
            $worksheet->mergeCells('A' . $row . ':O' . $row);
            $worksheet->getCell('A' . $row)->setValue($this->getRunTypeString($data->run_type) . ' PAYROLL REGISTRY REPORT');
            $worksheet->getStyle('A' . $row)->getFont()->setSize(16);
            $worksheet->getRowDimension($row)->setRowHeight(24);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFont()->setBold(true);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCFE2F3');
            $row++;

            $worksheet->mergeCells('A' . $row . ':O' . $row);
            $worksheet->getCell('A' . $row)->setValue($data->report_title);
            $worksheet->getStyle('A' . $row)->getFont()->setSize(16);
            $worksheet->getRowDimension($row)->setRowHeight(24);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFont()->setBold(true);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCFE2F3');
            foreach($data->report_subtitle as $subtitle) {
                $row++; $worksheet->insertNewRowBefore($row, 1);
                $worksheet->mergeCells('A' . $row . ':O' . $row);
                $worksheet->getCell('A' . $row)->setValue($subtitle);
                $worksheet->getStyle('A' . $row)->getFont()->setSize(16);
                $worksheet->getRowDimension($row)->setRowHeight(24);
            }
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $row++;
            $row++;
            $worksheet->getStyle('A' . $row . ':P' . $row)->getFont()->setSize(12);
            $worksheet->getCell('A' . $row)->setValue('Entries');
            $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('B' . $row)->setValue('---');
            $worksheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->mergeCells('E' . $row . ':G' . $row);
            $worksheet->getCell('D' . $row)->setValue('Payroll Name:');
            $worksheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('E' . $row)->setValue($data->payroll_name);
            $row++;
            $worksheet->getStyle('A' . $row . ':P' . $row)->getFont()->setSize(12);
            $worksheet->mergeCells('E' . $row . ':G' . $row);
            $worksheet->getCell('A' . $row)->setValue('Payroll By:');
            $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('B' . $row)->setValue($data->created_by);
            $worksheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            if ($data->run_type == \App\Http\Controllers\PayrollRunController::RUN_TYPE_OFFCYCLE) {
                $worksheet->getCell('D' . $row)->setValue('Period:');
                $worksheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCell('E' . $row)->setValue($data->deduction_period);
            }
            else if ($data->run_type == \App\Http\Controllers\PayrollRunController::RUN_TYPE_OVERTIME) {
                $worksheet->getCell('D' . $row)->setValue('Overtime Period:');
                $worksheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCell('E' . $row)->setValue($data->deduction_period);
            }
            else {
                $worksheet->getCell('D' . $row)->setValue('Deduction Period:');
                $worksheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCell('E' . $row)->setValue($data->deduction_period);
            }

            $row++;
            $worksheet->getStyle('A' . $row . ':P' . $row)->getFont()->setSize(12);
            $worksheet->mergeCells('E' . $row . ':G' . $row);
            $worksheet->getCell('A' . $row)->setValue('Cards By:');
            $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('D' . $row)->setValue('Date Generated:');
            $worksheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('B' . $row)->setValue($data->created_by);
            $worksheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('E' . $row)->setValue($data->created_at);

            $row++;
            $worksheet->getStyle('A' . $row . ':P' . $row)->getFont()->setSize(12);
            $worksheet->getCell('A' . $row)->setValue('We acknowledge receipt of cash down opposite our name as full compensation for services rendered for the period covered');
            $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $row++;
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCFE2F3');
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFont()->setBold(true);
            $worksheet->getCell('A' . $row)->setValue('NO.');
            $worksheet->getCell('B' . $row)->setValue('EMPLOYEE NAME');
            $worksheet->getCell('C' . $row)->setValue('POSITION / SALARY GRADE');
            $worksheet->getCell('D' . $row)->setValue('MONTHLY SALARY');
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getAlignment()->setWrapText(true);
            $worksheet->mergeCells('E' . $row . ':F' . $row);
            $worksheet->getCell('E' . $row)->setValue('EARNINGS');
            $worksheet->getCell('G' . $row)->setValue('GROSS AMOUNT');
            $worksheet->mergeCells('H' . $row . ':M' . $row);
            $worksheet->getCell('H' . $row)->setValue('DEDUCTIONS');
            $worksheet->getCell('N' . $row)->setValue('TOTAL DEDUCTIONS');
            $worksheet->getCell('O' . $row)->setValue('NET AMOUNT');
            $worksheet->getCell('P' . $row)->setValue('SIGNATURE');
            $worksheet->getStyle('A' . $row . ':P' . $row)->applyFromArray($border);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->getRowDimension($row)->setRowHeight(45);
            $row++;

            //table content
            foreach ($chunk as $entry) {
                $worksheet->getCell('A' . $row)->setValue($entry->index);
                $worksheet->getCell('B' . $row)->setValue(strtoupper($entry->name));
                $worksheet->getCell('C' . $row)->setValue(
                    $entry->position_name . '/ SG' . $entry->salary_grade_id
                );
                $worksheet->getCell('D' . $row)->setValue(
                    number_format($entry->basic_pay, 2, '.', ',')
                );
                $worksheet->getCell('G' . $row)->setValue(
                    number_format($entry->total_gross, 2, '.', ',')
                );
                $worksheet->getCell('N' . $row)->setValue(
                    number_format($entry->total_deductions, 2, '.', ',')
                );
                $worksheet->getCell('O' . $row)->setValue(
                    number_format($entry->net_pay, 2, '.', ',')
                );
                $worksheet->getCell('P' . $row)->setValue(strtoupper($entry->name));

                $earning_arr = array_merge(
                    $entry->earnings,
                    $entry->reimbursements
                );
                $deduction_arr = array_merge(
                    $entry->deductions,
                    $entry->loans,
                    $entry->contributions
                );
                if (isset($entry->taxes)) {
                    $deduction_arr = array_merge($deduction_arr, $entry->taxes);
                }
                $deduc_chunks = array_chunk($deduction_arr, 3);
                $row_increment_count = (count($earning_arr) > count($deduc_chunks) ? count($earning_arr) : count($deduc_chunks));
                if ($row_increment_count < self::MIN_EARNINGS_NUM) {
                    $row_increment_count = self::MIN_EARNINGS_NUM;
                }
                $row_init = $row;
                for ($i = 0; $i < $row_increment_count; $i++) {
                    //deductions
                    if (isset($deduc_chunks[$i])) {
                        for ($d = 0; $d < count($deduc_chunks[$i]); $d++) {
                            if (isset($deduc_chunks[$i][$d])) {
                                $worksheet->getStyle($deduc_column[$d][0] . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                                $worksheet->getStyle($deduc_column[$d][1] . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $worksheet->getStyle($deduc_column[$d][1] . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                                $worksheet->getCell($deduc_column[$d][0] . $row)->setValue(
                                    isset($deduc_chunks[$i][$d]->short_name) ?
                                    $deduc_chunks[$i][$d]->short_name :
                                    $deduc_chunks[$i][$d]->title
                                );
                                $worksheet->getCell($deduc_column[$d][1] . $row)->setValue($deduc_chunks[$i][$d]->amount);
                            }
                        }
                    }
                    //earnings
                    if (isset($earning_arr[$i])) {
                        $worksheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $worksheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $worksheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

                        $worksheet->getCell('E' . $row)->setValue(
                            isset($earning_arr[$i]->short_name) ?
                            $earning_arr[$i]->short_name :
                            $earning_arr[$i]->title
                        );
                        $worksheet->getCell('F' . $row)->setValue($earning_arr[$i]->amount);
                    }
                    $worksheet->getStyle('H' . $row . ':M' . $row)->applyFromArray($border);
                    $worksheet->getStyle('E' . $row . ':F' . $row)->applyFromArray($border);
                    if ($i == ($row_increment_count - 1)) {
                        $worksheet->getStyle('A' . $row . ':P' . $row)
                            ->getBorders()
                            ->getBottom()
                            ->setBorderStyle(Border::BORDER_MEDIUM);
                    }
                    $row++;
                }
                //merging columns
                foreach ($merge_col as $key) {
                    $worksheet->mergeCells($key . $row_init . ":" . $key . ($row - 1));
                    if ($key!='A') {
                        $worksheet->getStyle($key . $row_init . ":" . $key . ($row - 1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                    }

                }
            }
            //breakdown per page
            $breakdown = $this->getBreakdown($chunk);

            $worksheet->mergeCells('A' . $row . ':' . 'P' . $row);
            $worksheet->getStyle('A' . $row . ':P' . $row)->applyFromArray($border);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCFE2F3');
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFont()->setBold(true);
            $worksheet->getRowDimension($row)->setRowHeight(45);
            $worksheet->getCell('A' . $row)->setValue('TOTAL (' . $chunk[0]->index . ' to ' . $chunk[COUNT($chunk) - 1]->index . ')');
            $row++;

            // headers of breakdown
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getAlignment()->setWrapText(true);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCFE2F3');
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFont()->setBold(true);
            $worksheet->getRowDimension($row)->setRowHeight(45);

            $worksheet->mergeCells('A' . $row . ':C' . $row);
            $worksheet->mergeCells('E' . $row . ':F' . $row);
            $worksheet->mergeCells('H' . $row . ':M' . $row);
            $worksheet->getCell('D' . $row)->setValue('MONTHLY SALARY');
            $worksheet->getCell('E' . $row)->setValue('EARNINGS');
            $worksheet->getCell('G' . $row)->setValue('GROSS AMOUNT');
            $worksheet->getCell('H' . $row)->setValue('DEDUCTIONS');
            $worksheet->getCell('N' . $row)->setValue('TOTAL DEDUCTIONS');
            $worksheet->getCell('O' . $row)->setValue('NET AMOUNT');

            $worksheet->getStyle('A' . $row . ':P' . $row)->applyFromArray($border);
            $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            $breakdown_init_row = $row;
            $worksheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('O' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $worksheet->getStyle('D' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->getStyle('G' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->getStyle('N' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->getStyle('O' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $worksheet->getStyle('D' . $row )->getFont()->setBold(true);
            $worksheet->getStyle('G' . $row )->getFont()->setBold(true);
            $worksheet->getStyle('N' . $row )->getFont()->setBold(true);
            $worksheet->getStyle('O' . $row )->getFont()->setBold(true);

            $worksheet->getCell('D' . $row)->setValue($breakdown['monthly_salary']);
            $worksheet->getCell('G' . $row)->setValue($breakdown['gross_amount']);
            $worksheet->getCell('N' . $row)->setValue($breakdown['total_deductions']);
            $worksheet->getCell('O' . $row)->setValue($breakdown['net_amount']);


            $deduc_chunks = array_chunk($breakdown['deductions_breakdown'], 3);

            $row_increment_total_count = (count($breakdown['earnings_breakdown']) > count($deduc_chunks) ?
                count($breakdown['earnings_breakdown']) : count($deduc_chunks));
            if ($row_increment_total_count < self::MIN_EARNINGS_NUM) {
                $row_increment_total_count = self::MIN_EARNINGS_NUM;
            }

            $keys_deduc = array_keys($breakdown['deductions_breakdown']);
            $keys_deduc_chunks = array_chunk($keys_deduc, 3);

            $keys_earn = array_keys($breakdown['earnings_breakdown']);
            $lookup = $breakdown['lookup'];

            for ($i = 0; $i < $row_increment_total_count; $i++) {
                //deductions
                if (isset($deduc_chunks[$i])) {
                    for ($d = 0; $d < count($deduc_chunks[$i]); $d++) {
                        if (isset($deduc_chunks[$i][$d])) {
                            $worksheet->getStyle($deduc_column_total[$d][0] . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                            $worksheet->getStyle($deduc_column_total[$d][1] . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            $worksheet->getStyle($deduc_column_total[$d][1] . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                            $worksheet->getStyle($deduc_column_total[$d][0] . $row)->getFont()->setBold(true);
                            $worksheet->getStyle($deduc_column_total[$d][1] . $row)->getFont()->setBold(true);
                            $worksheet->getStyle($deduc_column_total[$d][0] . $row)->getAlignment()->setShrinkToFit(true);
                            $worksheet->getStyle($deduc_column_total[$d][0] . $row)->getAlignment()->setWrapText(false);
                                $worksheet->getCell($deduc_column_total[$d][0] . $row)->setValue(
                                isset($lookup[$keys_deduc_chunks[$i][$d]]) ?
                                $lookup[$keys_deduc_chunks[$i][$d]] :
                                $keys_deduc_chunks[$i][$d]
                            );
                            $worksheet->getCell($deduc_column_total[$d][1] . $row)->setValue($deduc_chunks[$i][$d]);
                        }
                    }
                }
                if (isset($keys_earn[$i]) && isset($breakdown['earnings_breakdown'][$keys_earn[$i]])) {
                    $worksheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $worksheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $worksheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $worksheet->getStyle('E' . $row)->getFont()->setBold(true);
                    $worksheet->getStyle('F' . $row)->getFont()->setBold(true);
                    $worksheet->getStyle('E' . $row)->getAlignment()->setShrinkToFit(true);
                    $worksheet->getStyle('E' . $row)->getAlignment()->setWrapText(false);
                    $worksheet->getCell('E' . $row)->setValue(
                        isset($lookup[$keys_earn[$i]]) ?
                        $lookup[$keys_earn[$i]] :
                        $keys_earn[$i]
                    );
                    $worksheet->getCell('F' . $row)->setValue($breakdown['earnings_breakdown'][$keys_earn[$i]]);
                }
                $worksheet->getStyle('E' . $row . ':F' . $row)->applyFromArray($border);
                $worksheet->getStyle('H' . $row . ':M' . $row)->applyFromArray($border);
                if ($i == ($row_increment_total_count - 1)) {
                    $worksheet->getStyle('A' . $row . ':P' . $row)
                        ->getBorders()
                        ->getBottom()
                        ->setBorderStyle(Border::BORDER_MEDIUM);
                }

                $row++;
            }
            $worksheet->mergeCells('A' . $breakdown_init_row . ":" . 'C' . ($row - 1));
            $worksheet->mergeCells('D' . $breakdown_init_row . ":" . 'D' . ($row - 1));
            $worksheet->mergeCells('G' . $breakdown_init_row . ":" . 'G' . ($row - 1));
            $worksheet->mergeCells('N' . $breakdown_init_row . ":" . 'N' . ($row - 1));
            $worksheet->mergeCells('O' . $breakdown_init_row . ":" . 'O' . ($row - 1));
            $worksheet->mergeCells('P' . $breakdown_init_row . ":" . 'P' . ($row - 1));
            $worksheet->getStyle('O' . $breakdown_init_row . ":" . 'P' . ($row - 1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyle('O' . $breakdown_init_row . ":" . 'O' . ($row - 1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyle('D' . $breakdown_init_row . ":" . 'D' . ($row - 1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);

            $worksheet->getStyle('A' . $startRow . ':' . 'A' . ($row-1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
            $worksheet->getStyle('P' . $startRow . ':' . 'P' . ($row-1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);

            $index++;
            $worksheet->setBreak('A' . $row, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW);
            $row++;
        }
        //grand total breakdown
        $row = $row + 2;
        $startRow = $row;
        $page = 'Page ' . (COUNT($registry_chunks) + 1) . ' of ' . (COUNT($registry_chunks) + 1);
        $worksheet->getStyle('A' . $row)->getFont()->setSize(16);
        $worksheet->getRowDimension($row)->setRowHeight(24);
        $worksheet->getCell('P' . $row)->setValue($page);
        $row++;
        $worksheet->getCell('A' . $row)->setValue($this->getRunTypeString($data->run_type) . ' PAYROLL REGISTRY REPORT');
        $worksheet->getStyle('A' . $row)->getFont()->setSize(16);
        $worksheet->getRowDimension($row)->setRowHeight(24);
        $row++;
        $worksheet->getCell('A' . $row)->setValue($data->report_title);
        $worksheet->getStyle('A' . $row)->getFont()->setSize(16);
        $worksheet->getRowDimension($row)->setRowHeight(24);
        foreach($data->report_subtitle as $subtitle) {
            $row++; $worksheet->insertNewRowBefore($row, 1);
            $worksheet->mergeCells('A' . $row . ':O' . $row);
            $worksheet->getCell('A' . $row)->setValue($subtitle);
            $worksheet->getStyle('A' . $row)->getFont()->setSize(16);
            $worksheet->getRowDimension($row)->setRowHeight(24);
            }

        $row++;
        $row++;
        $worksheet->getCell('A' . $row)->setValue('Entries');
        $worksheet->getCell('B' . $row)->setValue('---');
        $worksheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $worksheet->getCell('E' . $row)->setValue($data->payroll_name);
        $row++;
        $worksheet->getCell('B' . $row)->setValue($data->created_by);
        if ($data->run_type == \App\Http\Controllers\PayrollRunController::RUN_TYPE_OFFCYCLE) {
            $worksheet->getCell('D' . $row)->setValue('Period:');
            $worksheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('E' . $row)->setValue($data->deduction_period);
        }
        else if ($data->run_type == \App\Http\Controllers\PayrollRunController::RUN_TYPE_OVERTIME) {
            $worksheet->getCell('D' . $row)->setValue('Overtime Period:');
            $worksheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('E' . $row)->setValue($data->deduction_period);
        }
        else {
            $worksheet->getCell('D' . $row)->setValue('Deduction Period:');
            $worksheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getCell('E' . $row)->setValue($data->deduction_period);
        }
        $row++;
        $worksheet->getCell('B' . $row)->setValue($data->created_by);
        $worksheet->getCell('E' . $row)->setValue($data->created_at);
        $row = $row + 3;

        $breakdown_grand = $this->getBreakdown($data->entries);

        // headers of breakdown
        $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getAlignment()->setWrapText(true);
        $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCFE2F3');
        $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getFont()->setBold(true);
        $worksheet->getRowDimension($row)->setRowHeight(45);

        $worksheet->mergeCells('A' . $row . ':C' . $row);
        $worksheet->mergeCells('E' . $row . ':F' . $row);
        $worksheet->mergeCells('H' . $row . ':M' . $row);
        $worksheet->getCell('D' . $row)->setValue('MONTHLY SALARY');
        $worksheet->getCell('E' . $row)->setValue('EARNINGS');
        $worksheet->getCell('G' . $row)->setValue('GROSS AMOUNT');
        $worksheet->getCell('H' . $row)->setValue('DEDUCTIONS');
        $worksheet->getCell('N' . $row)->setValue('TOTAL DEDUCTIONS');
        $worksheet->getCell('O' . $row)->setValue('NET AMOUNT');

        $worksheet->getStyle('A' . $row . ':P' . $row)->applyFromArray($border);
        $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('A' . $row . ':' . 'P' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $row++;

        $worksheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $worksheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $worksheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $worksheet->getStyle('O' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

        $worksheet->getStyle('D' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('G' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('N' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('O' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $worksheet->getStyle('D' . $row)->getFont()->setBold(true);
        $worksheet->getStyle('G' . $row)->getFont()->setBold(true);
        $worksheet->getStyle('N' . $row)->getFont()->setBold(true);
        $worksheet->getStyle('O' . $row)->getFont()->setBold(true);

        $worksheet->getCell('D' . $row)->setValue($breakdown_grand['monthly_salary']);
        $worksheet->getCell('G' . $row)->setValue($breakdown_grand['gross_amount']);
        $worksheet->getCell('N' . $row)->setValue($breakdown_grand['total_deductions']);
        $worksheet->getCell('O' . $row)->setValue($breakdown_grand['net_amount']);

        $deduc_chunks_grand = array_chunk($breakdown_grand['deductions_breakdown'], 3);

        $row_increment_grand_total_count = (count($breakdown_grand['earnings_breakdown']) > count($deduc_chunks_grand) ?
            count($breakdown_grand['earnings_breakdown']) : count($deduc_chunks_grand));
        if ($row_increment_grand_total_count < self::MIN_EARNINGS_NUM) {
            $row_increment_grand_total_count = self::MIN_EARNINGS_NUM;
        }
        $keys_deduc = array_keys($breakdown_grand['deductions_breakdown']);
        $keys_deduc_chunks = array_chunk($keys_deduc, 3);

        $keys_earn = array_keys($breakdown_grand['earnings_breakdown']);
        $breakdown_init_row = $row;
        $lookup = $breakdown_grand['lookup'];

        $worksheet->insertNewRowBefore(
            $row + 1,
            ($row_increment_grand_total_count)
        );

        for ($i = 0; $i < $row_increment_grand_total_count; $i++) {
            if (isset($deduc_chunks_grand[$i])) {
                for ($d = 0; $d < count($deduc_chunks_grand[$i]); $d++) {
                    if (isset($deduc_chunks_grand[$i][$d])) {
                        $worksheet->getStyle($deduc_column_total[$d][0] . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $worksheet->getStyle($deduc_column_total[$d][1] . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $worksheet->getStyle($deduc_column_total[$d][1] . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                        $worksheet->getStyle($deduc_column_total[$d][0] . $row)->getFont()->setBold(true);
                        $worksheet->getStyle($deduc_column_total[$d][1] . $row)->getFont()->setBold(true);
                        $worksheet->getStyle($deduc_column_total[$d][0] . $row)->getAlignment()->setShrinkToFit(true);
                        $worksheet->getStyle($deduc_column_total[$d][0] . $row)->getAlignment()->setWrapText(false);
                        $worksheet->getCell($deduc_column_total[$d][0] . $row)->setValue(
                            isset($lookup[$keys_deduc_chunks[$i][$d]]) ?
                            $lookup[$keys_deduc_chunks[$i][$d]] :
                            $keys_deduc_chunks[$i][$d]
                        );
                        $worksheet->getCell($deduc_column_total[$d][1] . $row)->setValue($deduc_chunks_grand[$i][$d]);
                    }
                }
            }
            if (isset($keys_earn[$i]) && isset($breakdown_grand['earnings_breakdown'][$keys_earn[$i]])) {

                $worksheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $worksheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->getStyle('E' . $row)->getFont()->setBold(true);
                $worksheet->getStyle('F' . $row)->getFont()->setBold(true);
                $worksheet->getStyle('E' . $row)->getAlignment()->setShrinkToFit(true);
                $worksheet->getStyle('E' . $row)->getAlignment()->setWrapText(false);
                $worksheet->getCell('E' . $row)->setValue(
                    isset($lookup[$keys_earn[$i]]) ?
                    $lookup[$keys_earn[$i]] :
                    $keys_earn[$i]
                );
                $worksheet->getCell('F' . $row)->setValue($breakdown_grand['earnings_breakdown'][$keys_earn[$i]]);
            }
            $worksheet->getStyle('E' . $row . ':F' . $row)->applyFromArray($border);
            $worksheet->getStyle('H' . $row . ':M' . $row)->applyFromArray($border);
            if ($i == ($row_increment_grand_total_count - 1)) {
                $worksheet->getStyle('A' . $row . ':P' . $row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            }

            $row++;
        }
        $worksheet->mergeCells('A' . $breakdown_init_row . ":" . 'C' . ($row - 1));
        $worksheet->mergeCells('D' . $breakdown_init_row . ":" . 'D' . ($row - 1));
        $worksheet->mergeCells('G' . $breakdown_init_row . ":" . 'G' . ($row - 1));
        $worksheet->mergeCells('N' . $breakdown_init_row . ":" . 'N' . ($row - 1));
        $worksheet->mergeCells('O' . $breakdown_init_row . ":" . 'O' . ($row - 1));
        $worksheet->mergeCells('P' . $breakdown_init_row . ":" . 'P' . ($row - 1));

        $worksheet->getStyle('O' . $breakdown_init_row . ":" . 'P' . ($row - 1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle('O' . $breakdown_init_row . ":" . 'O' . ($row - 1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle('D' . $breakdown_init_row . ":" . 'D' . ($row - 1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);

        $worksheet->getStyle('A' . $startRow . ':' . 'A' . ($row-1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $worksheet->getStyle('P' . $startRow . ':' . 'P' . ($row-1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);

        $row = $row + 2;
        $worksheet->getCell('M' . $row)->setValue(
            $breakdown_grand['net_amount']
        );
        $row = $row + 1;
        $worksheet->getCell('G' . $row)->setValue(
            $util->numberTowords($breakdown_grand['net_amount'])
        );

    }


    private function getDynamicRowCount($data)
    {
        $rowCount = 0;
        $chunks_data = array_chunk($data, self::NUM_PER_PAGE);

        foreach ($chunks_data as $chunk) {
            $breakdown = $this->getBreakdown($chunk);
            $higher_row = count($breakdown['earnings_breakdown']) > count(array_chunk($breakdown['deductions_breakdown'], 3)) ?
                count($breakdown['earnings_breakdown']) : count(array_chunk($breakdown['deductions_breakdown'], 3));
                if ($higher_row < self::MIN_EARNINGS_NUM ) {
                    $higher_row = self::MIN_EARNINGS_NUM;
                }
            $rowCount += ($higher_row + 2);
        }

        foreach ($data as $item) {
            $earning_arr = array_merge(
                $item->earnings,
                $item->reimbursements
            );
            $deduction_arr = array_merge(
                $item->deductions,
                $item->loans,
                $item->contributions
            );
            if (isset($item->taxes)) {
                $deduction_arr = array_merge($deduction_arr, array($item->taxes));
            }
            $deduc_chunks = array_chunk($deduction_arr, 3);
            $count = (count($deduc_chunks) > count($earning_arr) ?
                count($deduc_chunks) :  count($earning_arr));
            if ($count < self::MIN_EARNINGS_NUM ) {
                $count = self::MIN_EARNINGS_NUM;
            }
            $rowCount = $rowCount + $count;
        }
        return $rowCount;
    }

    private function getBreakdown($data)
    {
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();
        $earnings_breakdown = [];
        $deductions_breakdown = [];
        $monthly_salary = 0;
        $gross_amount = 0;
        $total_deductions = 0;
        $net_amount = 0;
        $lookup = array();

        foreach ($data as $item) {
            $earnings = $item->earnings;
            $earnings = array_merge($earnings, $item->reimbursements);
            $deductions = array_merge($item->deductions, $item->loans, $item->contributions);
            $deductions = array_merge($deductions, $item->taxes);

            foreach ($earnings as $earningValue) {
                if (isset($earnings_breakdown[$earningValue->title])) {
                    $earnings_breakdown[$earningValue->title] += $earningValue->amount;
                } else {
                    $earnings_breakdown[$earningValue->title] = $earningValue->amount;
                }
                $lookup[$earningValue->title] = $earningValue->short_name ?? $earningValue->title;
            }
            foreach ($deductions as $deductionsValue) {
                if (isset($deductions_breakdown[$deductionsValue->title])) {
                    $deductions_breakdown[$deductionsValue->title] += $deductionsValue->amount;
                } else {
                    $deductions_breakdown[$deductionsValue->title] = $deductionsValue->amount;
                }
                $lookup[$deductionsValue->title] = $deductionsValue->short_name ?? $deductionsValue->title;
            }
            $monthly_salary += $item->basic_pay;
            $gross_amount += $item->total_gross;
            $total_deductions += $item->total_deductions;
            $net_amount += $item->net_pay;
        }
        return [
            "earnings_breakdown" => $earnings_breakdown,
            "deductions_breakdown" => $deductions_breakdown,
            "monthly_salary" => $monthly_salary,
            "total_deductions" => $total_deductions,
            "net_amount" => $net_amount,
            "gross_amount" => $gross_amount,
            "lookup" => $lookup,
        ];
    }

    private function formatPayPeriod($start, $end)
    {
        $payrollStart = Carbon::parse($start);
        $payrollEnd = Carbon::parse($end);

        if ($payrollStart->format('F') == $payrollEnd->format('F')) {
            $periodStr = $payrollStart->format('F') . ' '
            . $payrollStart->day . '-'
            . $payrollEnd->day . ', '
            . $payrollEnd->year;
        }
        else {
            $periodStr = $payrollStart->format('F') . ' '
            . $payrollStart->day . ' - '
            . $payrollEnd->format('F') . ' '
            . $payrollEnd->day . ', '
            . $payrollEnd->year;

        }
        return $periodStr;
    }

    private function getRunTypeString($run_type) {
        if ($run_type === \App\Http\Controllers\PayrollRunController::RUN_TYPE_REGULAR) {
            return 'REGULAR';
        }
        else if ($run_type === \App\Http\Controllers\PayrollRunController::RUN_TYPE_CONTRACTUAL) {
            return 'CONTRACT OF SERVICE';
        }
        else if ($run_type === \App\Http\Controllers\PayrollRunController::RUN_TYPE_OFFCYCLE) {
            return 'OFFCYCLE';
        }
        else if ($run_type === \App\Http\Controllers\PayrollRunController::RUN_TYPE_OVERTIME) {
            return 'OVERTIME';
        }
    }

}
