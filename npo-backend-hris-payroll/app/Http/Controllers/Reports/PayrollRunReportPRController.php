<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PayrollRunReportPRController extends Controller
{
    public function generatePRSheet($payrun, $data, $worksheet) {
        $util = new PayrollUtilController();
        $created_by = $payrun->createdBy->account_name;
        $position_name = $payrun->positionName->position_name;

        // write signatories
        $signatory = \App\Signatories::where('report_name', 'Payroll Report')->first();
        // write signatories
        $worksheet->getCell('D' . 32)->setValue(($signatory->signatories[0]['name'] ?? 'Name#1'));
        $worksheet->getCell('D' . 33)->setValue(($signatory->signatories[0]['name'] ?? 'Name#1'));
        $worksheet->getCell('I' . 32)->setValue(($signatory->signatories[1]['name'] ?? 'Name#1'));
        $worksheet->getCell('I' . 33)->setValue(($signatory->signatories[1]['title'] ?? 'Title#1'));
        $worksheet->getCell('N' . 32)->setValue(($signatory->signatories[2]['name'] ?? 'Name#2'));
        $worksheet->getCell('N' . 33)->setValue(($signatory->signatories[2]['title'] ?? 'Title#2'));

        $values = $data['data'];
        $totals = $util->create_totals_for_reports($values);

        //
        $is_include_pera_deductions = $totals['pera_deductions']['all'] > 0 || $payrun->run_type == \App\Http\Controllers\PayrollRunController::RUN_TYPE_REGULAR;
        // resize new columns and set formatting
        $num_of_items = 1 + sizeof($data['keys']['earnings']) +
            sizeof($data['keys']['earnings_underpaid']) + 1 + 1 +
            sizeof($data['keys']['earnings_overpaid']);
        if ($is_include_pera_deductions) {
            $num_of_items++;
        }

        if ($num_of_items < 8) {
            $num_of_items = 8;
        }

        // resize new columns and set formatting
        for($i=8; $i<$num_of_items; $i++) {
            $col = 5 + (2*$i) + 1;
            $small = $worksheet->getColumnDimensionByColumn(6)->getWidth();
            $normal = $worksheet->getColumnDimensionByColumn(7)->getWidth();
            $worksheet->getStyleByColumnAndRow($col, 6)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $worksheet->getColumnDimensionByColumn($col)->setWidth($small);
            $worksheet->getColumnDimensionByColumn($col+1)->setWidth($normal);
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getFont()->setBold(true);
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyleByColumnAndRow($col+1, 6)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // copy some styles
            foreach ([8,9,10,11] as $item) {
                $style = $worksheet->getStyleByColumnAndRow(7, $item);
                $worksheet->duplicateStyle($style, Coordinate::stringFromColumnIndex($col+1) . $item);
            }
        }

        $last_col = 5 + (2*($num_of_items-1)) + 1 + 1;
        $worksheet->setCellValueByColumnAndRow($last_col-2, 16, 'GRAND TOTAL');

        // merge columns based on $num_of_items
        $worksheet->mergeCellsByColumnAndRow(1,1,5+($num_of_items*2),1);
        $worksheet->setCellValueByColumnAndRow(1, 1, 'NATIONAL PRINTING OFFICE');
        $worksheet->mergeCellsByColumnAndRow(1,2,5+($num_of_items*2),2);
        $worksheet->setCellValueByColumnAndRow(1, 2, 'PAYROLL REPORT');
        $worksheet->mergeCellsByColumnAndRow(1,3,5+($num_of_items*2),3);
        $worksheet->setCellValueByColumnAndRow(1, 3, 'TITLE');
        $worksheet->mergeCellsByColumnAndRow(1,4,5+($num_of_items*2),4);
        $worksheet->setCellValueByColumnAndRow(1, 4, 'SUBTITLE');
        $worksheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // write headers
        $row = 6;
        $col = 7;
        foreach ($data['keys']['earnings'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }
        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Deductions'); $col+=2;
        if ($is_include_pera_deductions) {
            $worksheet->setCellValueByColumnAndRow($col, $row, 'PERA Deductions'); $col+=2;
        }

        foreach ($data['keys']['earnings_overpaid'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $item); $col+=2;
        }
        $col = 5 + ($num_of_items*2) - 2;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Ret. to Cash'); $col+=2;
        $worksheet->setCellValueByColumnAndRow($col, $row, 'Net Amount'); $col+=2;

        $departments = \App\Department::orderBy('pap_code')->get();
        $prev_pap_code = '';

        $row +=2;
        foreach($departments as $department) {
            $col = 2;
            if ($prev_pap_code == '') {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            else if ($prev_pap_code != $department->pap_code) {
                $prev_pap_code = $department->pap_code;
                $worksheet->setCellValueByColumnAndRow($col, $row, strtolower($department->pap_code));
            }
            $col+=2;
            $worksheet->setCellValueByColumnAndRow($col, $row, $department->department_name); $col+=3;

            if (isset($values[$department->department_name])) {
                foreach ($data['keys']['earnings'] as $item) {
                    $worksheet->setCellValueByColumnAndRow($col, $row, $values[$department->department_name]['earnings'][$item] ?? 0); $col+=2;
                }
                foreach ($data['keys']['earnings_underpaid'] as $item) {
                    $worksheet->setCellValueByColumnAndRow($col, $row, $values[$department->department_name]['earnings_underpaid'][$item] ?? 0); $col+=2;
                }
                $worksheet->setCellValueByColumnAndRow($col, $row,
                    $values[$department->department_name]['deductions']['all'] +
                    $values[$department->department_name]['taxes']['all']
                ); $col+=2;
                if ($is_include_pera_deductions) {
                    $worksheet->setCellValueByColumnAndRow($col, $row, $values[$department->department_name]['pera_deductions']['all']); $col+=2;
                }
                foreach ($data['keys']['earnings_overpaid'] as $item) {
                    $worksheet->setCellValueByColumnAndRow($col, $row, $values[$department->department_name]['earnings_overpaid'][$item] ?? 0); $col+=2;
                }
                $col = 5 + ($num_of_items*2) - 2;
                $worksheet->setCellValueByColumnAndRow($col, $row, 0); $col+=2;
                $worksheet->setCellValueByColumnAndRow($col, $row,
                    $values[$department->department_name]['net_amount']
                ); $col+=2;
            }
            $row++; $worksheet->insertNewRowBefore($row, 1);
        }
        // totals
        $worksheet->removeRow($row); $row++;
        $col = 7;
        foreach ($data['keys']['earnings'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings'][$item] ?? 0); $col+=2;
        }
        foreach ($data['keys']['earnings_underpaid'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings_underpaid'][$item] ?? 0); $col+=2;
        }
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['deductions']['all'] +
            $totals['taxes']['all']
        ); $col+=2;
        if ($is_include_pera_deductions) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['pera_deductions']['all']); $col+=2;
        }
        foreach ($data['keys']['earnings_overpaid'] as $item) {
            $worksheet->setCellValueByColumnAndRow($col, $row, $totals['earnings_overpaid'][$item] ?? 0); $col+=2;
        }
        $col = 5 + ($num_of_items*2) - 2;
        $worksheet->setCellValueByColumnAndRow($col, $row, 0); $col+=2;
        $worksheet->setCellValueByColumnAndRow($col, $row,
            $totals['net_amount']
        );
        $style=$worksheet->getStyleByColumnAndRow($col, $row);

        $sheets = $util->getSheetsFromPayrun($payrun);
        $worksheet->getCell('M'.($row+4))->setValue('1 - ' . $sheets .' Sheet/s');

        $worksheet->duplicateStyle($style, Coordinate::stringFromColumnIndex($last_col) . ($row+6));
        $worksheet->setCellValueByColumnAndRow($last_col, $row+6, $totals['net_amount']);


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
            $worksheet->mergeCellsByColumnAndRow(1,$row,5+($num_of_items*2),$row);
            $worksheet->setCellValueByColumnAndRow(1, $row, $item);
        }
        $row++; $worksheet->removeRow($row);

        $worksheet->getPageSetup()->setFitToWidth(1);
        $worksheet->getPageSetup()->setFitToHeight(0);
    }
}
