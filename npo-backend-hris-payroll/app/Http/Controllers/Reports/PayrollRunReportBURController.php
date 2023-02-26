<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PayrollRunReportBURController extends Controller
{
    public function generateBurSheet($payrun, $data, $worksheet) {
        $util = new PayrollUtilController();
        $signatory = \App\Signatories::where('report_name', 'BUR')->first();

        $worksheet->getCell('C37')->setValue(($signatory->signatories[0]['name'] ?? 'Name#1'));
        $worksheet->getCell('C39')->setValue(($signatory->signatories[0]['title'] ?? 'Title#1'));
        $worksheet->getCell('K37')->setValue(($signatory->signatories[1]['name'] ?? 'Name#2'));
        $worksheet->getCell('K39')->setValue(($signatory->signatories[1]['title'] ?? 'Title#2'));

        $values = $data['data'];
        $str = 'Payment for Regular Salary and PERA of the Officials and Employees of National Printing Office ';
        $pre = '            ';
        if ($data['type'] == $util::REPORT_TYPE_FULL) {
            $str = $payrun->bur_dv_description == '' ? 'Please fill up bur_dv_description and re-download report' : $payrun->bur_dv_description;
        }
        else {
            $payroll_dates = $util->get_start_end_from_payrun($payrun, $data['type']);
            $str .= $payroll_dates[0]->isoFormat('MMM D') . ' - ' . $payroll_dates[1]->isoFormat('D, YYYY');
        }

        $worksheet->getCell('D' . 16)->setValue($pre . $str);

        $totals = $util->create_totals_for_reports($values);

        $row = 23;
        $worksheet->getCell('D' . $row)->setValue('Gross Amount');
        $worksheet->getCell('H' . $row)->setValue($totals['earnings']['all'] + $totals['earnings_underpaid']['all']);
        foreach($totals['earnings_overpaid'] as $key => $value) {
            if ($key != 'all') {
                $worksheet->getStyle('H' . $row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);
                $row++; $worksheet->insertNewRowBefore($row, 1);
                $worksheet->getCell('D' . $row)->setValue($key);
                $worksheet->getCell('H' . $row)->setValue($value);
                $worksheet->mergeCells('D' . $row . ':G' . $row );
            }
        }
        $row++;
        $worksheet->getStyle('H' . $row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getCell('H' . $row)->setValue(
            $totals['earnings']['all']
            + $totals['earnings_underpaid']['all']
            - $totals['earnings_overpaid']['all']
        );
        $row = $row + 3;
        $worksheet->getCell('L' . $row)->setValue(
            $totals['earnings']['all']
            + $totals['earnings_underpaid']['all']
            - $totals['earnings_overpaid']['all']
        );


    }
}
