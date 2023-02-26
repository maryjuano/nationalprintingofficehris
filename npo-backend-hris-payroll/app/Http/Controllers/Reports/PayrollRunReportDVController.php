<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PayrollRunReportDVController extends Controller
{
    public function generateDVSheet($payrun, $data, $worksheet) {
        $worksheet->getDefaultRowDimension()->setRowHeight(13);
        $util = new PayrollUtilController();
        $signatory = \App\Signatories::where('report_name', 'DV')->first();

        $values = $data['data'];
        $totals = $util->create_totals_for_reports($values);

        $worksheet->getCell('Q22')->setValue(($signatory->signatories[0]['name'] ?? 'Name#1'));
        $worksheet->getCell('Q23')->setValue(($signatory->signatories[0]['title'] ?? 'Title#1'));
        $worksheet->getCell('E41')->setValue(($signatory->signatories[1]['name'] ?? 'Name#2'));
        $worksheet->getCell('E43')->setValue(($signatory->signatories[1]['title'] ?? 'Title#2'));
        $worksheet->getCell('AA41')->setValue(($signatory->signatories[2]['name'] ?? 'Name#3'));
        $worksheet->getCell('AA43')->setValue(($signatory->signatories[2]['title'] ?? 'Title#3'));

        $str = 'Payment for Regular Salary and PERA of the Officials and Employees of National Printing Office ';
        $pre = "\n\n     ";
        if ($data['type'] == $util::REPORT_TYPE_FULL) {
            $str = $payrun->bur_dv_description == '' ? 'Please fill up bur_dv_description and re-download report' : $payrun->bur_dv_description;
        }
        else {
            $payroll_dates = $util->get_start_end_from_payrun($payrun, $data['type']);
            $str .= $payroll_dates[0]->isoFormat('MMM D') . ' - ' . $payroll_dates[1]->isoFormat('D, YYYY');
        }

        $worksheet->getCell('C' . 14)->setValue($pre . $str);
        $worksheet->getCell('AE' . 15)->setValue($totals['net_amount']);
        $worksheet->getCell('AE' . 21)->setValue($totals['net_amount']);

    }
}
