<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PayrollRunReportCAController extends Controller
{
    const P_FORMAT = '_-[$₱-en-PH]* #,##0.00_ ;_-[$₱-en-PH]* -#,##0.00 ;_-[$₱-en-PH]* "-"??_ ;_-@_ ';

    public function generateCASheet($payrun, $data, $worksheet) {
        $util = new PayrollUtilController();
        $created_by = $payrun->createdBy->account_name;
        $position_name = $payrun->positionName->position_name;
        $signatory = \App\Signatories::where('report_name', 'Cash Advance Regular')->first();

        // write signatories
        // $worksheet->getCell('A' . 26)->setValue($created_by);
        // $worksheet->getCell('A' . 27)->setValue($position_name);
        $worksheet->getCell('C' . 35)->setValue(($signatory->signatories[0]['name'] ?? 'Name#1'));
        $worksheet->getCell('C' . 36)->setValue(($signatory->signatories[0]['title'] ?? 'Title#1'));
        $worksheet->getCell('G' . 35)->setValue(($signatory->signatories[1]['name'] ?? 'Name#2'));
        $worksheet->getCell('G' . 36)->setValue(($signatory->signatories[1]['title'] ?? 'Title#2'));

        $num_of_items = 2 + sizeof($data['keys']['earnings']) + sizeof($data['keys']['earnings_underpaid']);
        if ($num_of_items < 4) {
            $num_of_items = 4;
        }

        // resize new columns and set formatting
        for($i=4; $i<$num_of_items; $i++) {
            $col = 2 + (2*$i) + 1;
            $small = $worksheet->getColumnDimensionByColumn(5)->getWidth();
            $normal = $worksheet->getColumnDimensionByColumn(6)->getWidth();
            $worksheet->getStyleByColumnAndRow($col, 6)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $worksheet->getColumnDimensionByColumn($col)->setWidth($small);
            $worksheet->getColumnDimensionByColumn($col+1)->setWidth($normal);
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getFont()->setBold(true);
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // copy some styles
            foreach ([8,9,10,11,12,13,14,15,16,17,18,19,24] as $item) {
                $style = $worksheet->getStyleByColumnAndRow(6, $item);
                $worksheet->duplicateStyle($style, Coordinate::stringFromColumnIndex($col+1) . $item);
            }
        }

        // merge columns based on $num_of_items
        $worksheet->mergeCellsByColumnAndRow(1,1,2+($num_of_items*2),1);
        $worksheet->setCellValueByColumnAndRow(1, 1, 'NATIONAL PRINTING OFFICE');
        $worksheet->mergeCellsByColumnAndRow(1,2,2+($num_of_items*2),2);
        $worksheet->setCellValueByColumnAndRow(1, 2, 'CASH ADVANCE');
        $worksheet->mergeCellsByColumnAndRow(1,3,2+($num_of_items*2),3);
        $worksheet->setCellValueByColumnAndRow(1, 3, 'TITLE');
        $worksheet->mergeCellsByColumnAndRow(1,4,2+($num_of_items*2),4);
        $worksheet->setCellValueByColumnAndRow(1, 4, 'SUBTITLE');
        $worksheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $worksheet->mergeCellsByColumnAndRow(4,22,2+($num_of_items*2),22);
        $worksheet->getStyle('D13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->setCellValueByColumnAndRow(4, 22, 'R E C A P I T U L A T I O N');


        // write headers
        $row = 6;
        $col = 4;
        foreach ($data['keys']['earnings'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }
        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }
        $col = 2 + ($num_of_items*2) - 2;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Deductions'); $col+=2;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Net Amount');


        $row += 2;
        $departments = \App\Department::orderBy('pap_code')->get();
        $prev_pap_code = '';
        $values = $data['data'];
        foreach($departments as $department) {
            $col = 1;
            if ($prev_pap_code == '') {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            else if ($prev_pap_code != $department->pap_code) {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            $col++;
            $worksheet->setCellValueByColumnAndRow($col, $row, $department->department_name); $col+=2;

            if (isset($values[$department->department_name])) {
                foreach ($data['keys']['earnings'] as $item) {
                    $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $worksheet->setCellValueByColumnAndRow($col, $row, $values[$department->department_name]['earnings'][$item] ?? '-'); $col+=2;
                }
                foreach ($data['keys']['earnings_underpaid'] as $item) {
                    $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $worksheet->setCellValueByColumnAndRow($col, $row, $values[$department->department_name]['earnings_underpaid'][$item] ?? '-'); $col+=2;
                }
                $col = 2 + ($num_of_items*2) - 2;
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $s = $values[$department->department_name]['deductions']['all'] +
                    $values[$department->department_name]['taxes']['all'] +
                    $values[$department->department_name]['pera_deductions']['all'] +
                    $values[$department->department_name]['earnings_overpaid']['all'];
                $worksheet->setCellValueByColumnAndRow($col, $row,
                    ($s == 0 ? '-': $s)
                ); $col+=2;
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $s = $values[$department->department_name]['net_amount'];
                $worksheet->setCellValueByColumnAndRow($col, $row,
                    ($s == 0 ? '-': $s)
                ); $col++;
            }
            $row++; $worksheet->insertNewRowBefore($row, 1);
        }

        // totals
        $worksheet->removeRow($row); $row++;
        $col = 4;
        $totals = $util->create_totals_for_reports($values);
        foreach ($data['keys']['earnings'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings'][$item] ?? '-'); $col+=2;
        }
        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings_underpaid'][$item] ?? '-'); $col+=2;
        }
        $col = 2 + ($num_of_items*2) - 2;
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['deductions']['all'] +
            $totals['taxes']['all'] +
            $totals['pera_deductions']['all'] +
            $totals['earnings_overpaid']['all']
        ); $col+=2;
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['net_amount']
        ); $col++;

        // BUDGET SUMMARY
        // write headers
        $row = $row + 3;
        $col = 4;
        foreach ($data['keys']['earnings'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }
        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }
        $col = 2 + ($num_of_items*2) - 2;
        $worksheet->setCellValueByColumnAndRow($col, $row, ' ');
        $col+=2;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Net Amount');

        $row+=2;
        $prev_pap_code = '';
        $values = $data['data'];
        foreach($departments as $department) {
            $col = 1;
            if ($prev_pap_code == '') {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            else if ($prev_pap_code != $department->pap_code) {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            $col++;
            $worksheet->setCellValueByColumnAndRow($col, $row, $department->department_name); $col+=2;

            if (isset($values[$department->department_name])) {
                foreach ($data['keys']['earnings'] as $item) {
                    $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $worksheet->setCellValueByColumnAndRow($col, $row, $values[$department->department_name]['earnings'][$item] ?? '-'); $col+=2;
                }
                foreach ($data['keys']['earnings_underpaid'] as $item) {
                    $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $worksheet->setCellValueByColumnAndRow($col, $row, $values[$department->department_name]['earnings_underpaid'][$item] ?? '-'); $col+=2;
                }
                $col = 2 + ($num_of_items*2) - 2;
                // $worksheet->setCellValueByColumnAndRow($col, $row,
                //     $values[$department->department_name]['deductions']['all'] +
                //     $values[$department->department_name]['taxes']['all'] +
                //     $values[$department->department_name]['pera_deductions']['all'] +
                //     $values[$department->department_name]['earnings_overpaid']['all']
                // );
                $col+=2;
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $s = $values[$department->department_name]['net_amount'] + (
                    $values[$department->department_name]['deductions']['all'] +
                    $values[$department->department_name]['taxes']['all'] +
                    $values[$department->department_name]['pera_deductions']['all'] +
                    $values[$department->department_name]['earnings_overpaid']['all']
                );
                $worksheet->setCellValueByColumnAndRow($col, $row,
                    ($s == 0 ? '-': $s)
                ); $col++;
            }
            $row++; $worksheet->insertNewRowBefore($row, 1);
        }

        // totals
        $worksheet->removeRow($row); $row++;
        $col = 4;
        $totals = $util->create_totals_for_reports($values);
        foreach ($data['keys']['earnings'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings'][$item] ?? '-'); $col+=2;
        }
        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings_underpaid'][$item] ?? '-'); $col+=2;
        }
        $col = 2 + ($num_of_items*2) - 2;
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        // $worksheet->setCellValueByColumnAndRow($col, $row,
        //     $totals['deductions']['all'] +
        //     $totals['taxes']['all'] +
        //     $totals['pera_deductions']['all'] +
        //     $totals['earnings_overpaid']['all']
        // );
        $col+=2;
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['net_amount'] + (
                $totals['deductions']['all'] +
                $totals['taxes']['all'] +
                $totals['pera_deductions']['all'] +
                $totals['earnings_overpaid']['all'])
        ); $col++;

        $row+=2;
        $col = 2 + ($num_of_items*2);
        $worksheet->getStyleByColumnAndRow($col-2,$row)->getFont()->setBold(true);
        $worksheet->setCellValueByColumnAndRow($col-2, $row,'BUR TOTAL');
        $worksheet->getStyleByColumnAndRow($col,$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $worksheet->getStyleByColumnAndRow($col,$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyleByColumnAndRow($col,$row)->getFont()->setBold(true);
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('_-[$₱-en-PH]* #,##0.00_ ;_-[$₱-en-PH]* -#,##0.00 ;_-[$₱-en-PH]* "-"??_ ;_-@_ ');

        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['net_amount'] + (
                $totals['deductions']['all'] +
                $totals['taxes']['all'] +
                $totals['pera_deductions']['all'] +
                $totals['earnings_overpaid']['all']
            )
        ); $col++;


        $row+=5;
        // per pap code totals
        $pap_totals = $util->create_total_by_pap_code($values);
        foreach ($pap_totals as $pap => $totals) {
            $col = 3;
            $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($pap)); $col+=1;

            foreach ($data['keys']['earnings'] as $item) {
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings'][$item] ?? '-'); $col+=2;
            }
            foreach ($data['keys']['earnings_underpaid'] as $item) {
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings_underpaid'][$item] ?? '-'); $col+=2;
            }
            $col = 2 + ($num_of_items*2) - 2;
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->setCellValueByColumnAndRow($col, $row,
                $totals['deductions']['all'] +
                $totals['taxes']['all'] +
                $totals['pera_deductions']['all'] +
                $totals['earnings_overpaid']['all']
            ); $col+=2;
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->setCellValueByColumnAndRow($col, $row,
                $totals['net_amount']
            ); $col++;
            $row++; $worksheet->insertNewRowBefore($row, 1);
        }

        // totals (copy from above)
        $worksheet->removeRow($row); $row+=1;
        $col = 4;
        $totals = $util->create_totals_for_reports($values);
        foreach ($data['keys']['earnings'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings'][$item] ?? '-'); $col+=2;
        }
        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings_underpaid'][$item] ?? '-'); $col+=2;
        }
        $col = 2 + ($num_of_items*2) - 2;
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['deductions']['all'] +
            $totals['taxes']['all'] +
            $totals['pera_deductions']['all'] +
            $totals['earnings_overpaid']['all']
        ); $col+=2;
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['net_amount']
        );
        $row+=4;
        $sheets = $util->getSheetsFromPayrun($payrun);
        $worksheet->getCell('F'.$row)->setValue('1 - ' . $sheets .' Sheet/s');

        $row+=3;
        $worksheet->getStyleByColumnAndRow($col-2,$row)->getFont()->setBold(true);
        $worksheet->setCellValueByColumnAndRow($col-2, $row,'DV TOTAL');
        $worksheet->getStyleByColumnAndRow($col,$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $worksheet->getStyleByColumnAndRow($col,$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyleByColumnAndRow($col,$row)->getFont()->setBold(true);
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('_-[$₱-en-PH]* #,##0.00_ ;_-[$₱-en-PH]* -#,##0.00 ;_-[$₱-en-PH]* "-"??_ ;_-@_ ');

        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['net_amount']
        ); $col++;

        // title and subtitle
        if ($data['type'] == $util::REPORT_TYPE_FULL) {
            $title = $payrun->title;
            $subtitle = $payrun->subtitle;
            if ($subtitle == null) {
                $subtitle = [''];
            }
        }
        else {
            $payroll_dates = $util->get_start_end_from_payrun($payrun, $data['type']);
            $title = 'REGULAR SALARY';
            $subtitle = [ 'For the Period of ' . $payroll_dates[0]->format('F d') . ' - ' . $payroll_dates[1]->format('d, Y')];
        }
        $row = 3;
        $worksheet->setCellValueByColumnAndRow(1, $row, $title);
        foreach($subtitle as $item) {
            $row++; $worksheet->insertNewRowBefore($row, 1);
            $worksheet->mergeCellsByColumnAndRow(1,$row,2+($num_of_items*2),$row);
            $worksheet->setCellValueByColumnAndRow(1, $row, $item);
        }
        $row++; $worksheet->removeRow($row);

        $worksheet->getPageSetup()->setFitToWidth(1);
        $worksheet->getPageSetup()->setFitToHeight(0);
        $worksheet->getPageSetup()->setFitToPage(1);
    }

    // ============

    public function generateCAAttachSheet($payrun, $data, $worksheet) {
        $util = new PayrollUtilController();

        // add fillers
        foreach (
            [
                \App\Adjustment::PREFIX_OVERPAID . ' ' . \App\Adjustment::CONST_BASIC_PAY,
                \App\Adjustment::PREFIX_OVERPAID . ' ' . \App\Adjustment::CONST_PERA_ALLOWANCE,
            ] as $item
        ) {
            if (!in_array($item, $data['keys']['earnings_overpaid'])) {
                array_push($data['keys']['earnings_overpaid'], $item);
            }
        }
        foreach (
            [
                \App\Adjustment::PREFIX_UNDERPAID . ' ' . \App\Adjustment::CONST_BASIC_PAY,
                \App\Adjustment::PREFIX_UNDERPAID . ' ' . \App\Adjustment::CONST_PERA_ALLOWANCE,
            ] as $item
        ) {
            if (!in_array($item, $data['keys']['earnings_underpaid'])) {
                array_push($data['keys']['earnings_underpaid'], $item);
            }
        }

        $num_first = 2 + sizeof($data['keys']['earnings_overpaid']);
        $num_second = sizeof($data['keys']['earnings_underpaid']) + sizeof($data['keys']['taxes']);
        $num_of_items = $num_first;
        if ($num_first < $num_second) {
            $num_of_items = $num_second;
        }

        // resize new columns
        for($i=4; $i<$num_of_items; $i++) {
            $col = 2 + (2*$i) + 1;
            $small = $worksheet->getColumnDimensionByColumn(5)->getWidth();
            $normal = $worksheet->getColumnDimensionByColumn(6)->getWidth();

            $worksheet->getColumnDimensionByColumn($col)->setWidth($small);
            $worksheet->getColumnDimensionByColumn($col+1)->setWidth($normal);
        }

        for($i=4; $i<$num_first; $i++) {
            $col = 2 + (2*$i) + 1;
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getFont()->setBold(true);
            $worksheet->getStyleByColumnAndRow($col, 6)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // copy some styles
            foreach ([8,9,10,11] as $item) {
                $style = $worksheet->getStyleByColumnAndRow(4, $item);
                $worksheet->duplicateStyle($style, Coordinate::stringFromColumnIndex($col+1) . $item);
            }
        }

        for($i=1; $i<$num_second; $i++) {
            $col = 2 + (2*$i) + 1;
            $worksheet->getStyleByColumnAndRow($col+1, 15)->getFont()->setBold(true);
            $worksheet->getStyleByColumnAndRow($col, 15)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyleByColumnAndRow($col+1, 15)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyleByColumnAndRow($col+1, 15)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // copy some styles
            foreach ([17,18,19] as $item) {
                $style = $worksheet->getStyleByColumnAndRow(4, $item);
                $worksheet->duplicateStyle($style, Coordinate::stringFromColumnIndex($col+1) . $item);
            }
        }

        // merge columns based on $num_of_items
        $worksheet->mergeCellsByColumnAndRow(1,1,2+($num_of_items*2),1);
        $worksheet->setCellValueByColumnAndRow(1, 1, 'NATIONAL PRINTING OFFICE');
        $worksheet->mergeCellsByColumnAndRow(1,2,2+($num_of_items*2),2);
        $worksheet->setCellValueByColumnAndRow(1, 2, 'CASH ADVANCE ATTACHMENT');
        $worksheet->mergeCellsByColumnAndRow(1,3,2+($num_of_items*2),3);
        $worksheet->setCellValueByColumnAndRow(1, 3, 'TITLE');
        $worksheet->mergeCellsByColumnAndRow(1,4,2+($num_of_items*2),4);
        $worksheet->setCellValueByColumnAndRow(1, 4, 'SUBTITLE');
        $worksheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // write headers
        $row = 6;
        $col = 4;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Deductions Salary'); $col+=2;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Deductions PERA'); $col+=2;
        foreach ($data['keys']['earnings_overpaid'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }

        $row+=2;
        $departments = \App\Department::orderBy('pap_code')->get();
        $prev_pap_code = '';
        $values = $data['data'];
        foreach($departments as $department) {
            $col = 1;
            if ($prev_pap_code == '') {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            else if ($prev_pap_code != $department->pap_code) {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            $col++;
            $worksheet->setCellValueByColumnAndRow($col, $row, $department->department_name); $col+=2;

            if (isset($values[$department->department_name])) {
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $s = $values[$department->department_name]['deductions']['all'] +
                    $values[$department->department_name]['taxes']['all'];
                $worksheet->setCellValueByColumnAndRow($col, $row,
                    ($s == 0 ? '-' : $s)
                ); $col+=2;
                $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $s = $values[$department->department_name]['pera_deductions']['all'];
                $worksheet->setCellValueByColumnAndRow($col, $row,
                    ($s == 0 ? '-' : $s)
                ); $col+=2;
                foreach ($data['keys']['earnings_overpaid'] as $item) {
                    $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $s = $values[$department->department_name]['earnings_overpaid'][$item] ?? '-';
                    $s = $s == 0 ? '-' : $s;
                    $worksheet->setCellValueByColumnAndRow($col, $row, $s); $col+=2;
                }
            }
            $row++; $worksheet->insertNewRowBefore($row, 1);
        }

        // totals
        $worksheet->removeRow($row); $row++;
        $col = 4;
        $totals = $util->create_totals_for_reports($values);
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['deductions']['all'] +
            $totals['taxes']['all']
        ); $col+=2;
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['pera_deductions']['all']
        ); $col+=2;
        foreach ($data['keys']['earnings_overpaid'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings_overpaid'][$item] ?? '-'); $col+=2;
        }

        $row+=3;
        // write deductions total
        $col = 2 + (2*$num_first);
        $worksheet->getStyleByColumnAndRow($col,$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $worksheet->getStyleByColumnAndRow($col,$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyleByColumnAndRow($col,$row)->getFont()->setBold(true);
        $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['deductions']['all'] +
            $totals['taxes']['all'] +
            $totals['pera_deductions']['all'] +
            $totals['earnings_overpaid']['all']
        );

        $row+=2;
        // ===== second set
        // write headers
        $col = 4;
        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }
        foreach ($data['keys']['taxes'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }

        $row+=2;
        $departments = \App\Department::orderBy('pap_code')->get();
        $prev_pap_code = '';
        $values = $data['data'];
        foreach($departments as $department) {
            $col = 1;
            if ($prev_pap_code == '') {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            else if ($prev_pap_code != $department->pap_code) {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            $col++;
            $worksheet->setCellValueByColumnAndRow($col, $row, $department->department_name); $col+=2;

            if (isset($values[$department->department_name])) {
                foreach ($data['keys']['earnings_underpaid'] as $item) {
                    $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $s = $values[$department->department_name]['earnings_underpaid'][$item] ?? '-';
                    $s = $s == 0? '-' : $s;
                    $worksheet->setCellValueByColumnAndRow($col, $row, $s); $col+=2;
                }
                foreach ($data['keys']['taxes'] as $item) {
                    $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $s = $values[$department->department_name]['taxes'][$item] ?? '-';
                    $s = $s == 0? '-' : $s;
                    $worksheet->setCellValueByColumnAndRow($col, $row, $s); $col+=2;
                }
            }
            $row++; $worksheet->insertNewRowBefore($row, 1);
        }

        // totals
        $worksheet->removeRow($row); $row++;
        $col = 4;
        $totals = $util->create_totals_for_reports($values);

        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings_underpaid'][$item] ?? '-'); $col+=2;
        }
        foreach ($data['keys']['taxes'] as $item) {
            $worksheet->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode(self::P_FORMAT);
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['taxes'][$item] ?? '-'); $col+=2;
        }
        $row++;

        // title and subtitle
        if ($data['type'] == $util::REPORT_TYPE_FULL) {
            $title = $payrun->title;
            $subtitle = $payrun->subtitle;
            if ($subtitle == null) {
                $subtitle = [''];
            }
        }
        else {
            $payroll_dates = $util->get_start_end_from_payrun($payrun, $data['type']);
            $title = 'REGULAR SALARY';
            $subtitle = [ 'For the Period of ' . $payroll_dates[0]->format('F d') . ' - ' . $payroll_dates[1]->format('d, Y')];
        }
        $row = 3;
        $worksheet->setCellValueByColumnAndRow(1, $row, $title);
        foreach($subtitle as $item) {
            $row++; $worksheet->insertNewRowBefore($row, 1);
            $worksheet->mergeCellsByColumnAndRow(1,$row,2+($num_of_items*2),$row);
            $worksheet->setCellValueByColumnAndRow(1, $row, $item);
        }
        $row++; $worksheet->removeRow($row);

        $worksheet->getPageSetup()->setFitToWidth(1);
        $worksheet->getPageSetup()->setFitToHeight(0);

    }


}
