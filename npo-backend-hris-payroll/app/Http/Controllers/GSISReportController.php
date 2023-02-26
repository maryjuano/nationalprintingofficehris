<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Reports\GSISCertificate;
use App\Reports\GSISExcelWorkbook;
use Maatwebsite\Excel\Facades\Excel;

class GSISReportController extends Controller
{
    public function excel(Request $request)
    {
        $type = $request->input('type');
        $certificates = collect($request->input('employees'))->map(function ($employee) use ($type) {
            return new GSISCertificate(
                $type,
                $employee['name'],
                $employee['period_start'],
                $employee['period_end'],
                $employee['or_details'],
                $employee['division'],
            );
        });
        $workbook = new GSISExcelWorkbook($type, $certificates->all());
        return Excel::download($workbook, 'gsis_excel.xlsx');
    }

    public function pdf(Request $request)
    {
        $type = $request->input('type');
        $certificates = collect($request->input('employees'))->map(function ($employee) use ($type) {
            $certificate = new GSISCertificate(
                $type,
                $employee['name'],
                $employee['period_start'],
                $employee['period_end'],
                $employee['or_details']
            );
            // return view('pdf.gsis_certificate', compact('certificate'));
            return \PDF::loadView('pdf.gsis_certificate', compact('certificate'))->setPaper('A4', 'portrait');
        });
        return $certificates[0]->stream('application_for_leave.pdf');
        //   return $certificates[0];
    }

   

    protected function getCertificates(Request $request)
    {
    }
}
