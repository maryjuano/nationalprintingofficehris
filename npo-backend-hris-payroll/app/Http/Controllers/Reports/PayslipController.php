<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use JWTAuth;
use DB;
use Carbon\Carbon;
use PDF;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class PayslipController extends Controller
{
    public function getRange(Request $request) {
        $unauthorized = $this->is_not_authorized(['payslip']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!$this->me->employee_details) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }
        return response()->json(
            [
                'start' => DB::table('payroll_history_for_all_employee')->min('payroll_period'),
                'end' => DB::table('payroll_history_for_all_employee')->max('payroll_period')
            ]
        );
    }

    public function downloadPayslipById(Request $request, $id) {
        $unauthorized = $this->is_not_authorized(['payslip']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();

        // Load all related payruns
        $payruns = \App\Payrun::where('id', $id)
            // ->where('status', \App\Payrun::PAYRUN_STATUS_COMPLETED)
            ->get();
        $date = Carbon::parse($payruns[0]->payroll_date)->format('F Y');
        $data = $util->combine_and_filter_payruns($payruns, null, true);

        $signatory = \App\Signatories::where('report_name', 'Payslips')->first();

        view()->share('vals', [
            'image' => "/images/logo.png",
            'data' => $data,
            'pay_date' =>  $date,
            'signatory' => $signatory
        ]);

        $pdf = PDF::loadView('pdf.payslip')->setPaper('letter', 'portrait');
        return $pdf->stream('payslip.pdf');
        return $pdf->download('payslip.pdf');
        return view('pdf.payslip');
    }

    public function downloadPayslipByDate(Request $request) {
        $unauthorized = $this->is_not_authorized(['payslip']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();

        $start = $request->input('id');
        $end = Carbon::createFromFormat('Y-m-d', $start)
            ->addMonth()
            ->firstOfMonth()
            ->format('Y-m-d');
        // Load all related payruns
        $payruns = \App\Payrun::where('payroll_date', '>=', $start)
            ->where('payroll_date', '<', $end)
            ->where('status', \App\Payrun::PAYRUN_STATUS_COMPLETED)
            ->get();
        // construct data
        // Log::debug(json_encode($payruns));
        $data = $util->combine_and_filter_payruns($payruns, null, true);
        // Log::debug($data);

        $date = Carbon::parse($request->input('id'))->format('F Y');
        $signatory = \App\Signatories::where('report_name', 'Payslips')->first();

        view()->share('vals', [
            'image' => "/images/logo.png",
            'data' => $data,
            'pay_date' =>  $date,
            'signatory' => $signatory
        ]);

        $pdf = PDF::loadView('pdf.payslip')->setPaper('letter', 'portrait');
        return $pdf->stream('payslip.pdf');
        return $pdf->download('payslip.pdf');
        return view('pdf.payslip');

    }

    public function downloadMyPayslip(Request $request) {
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!$this->me->employee_details) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();

        $start = $request->input('id');
        $end = Carbon::createFromFormat('Y-m-d', $start)
            ->addMonth()
            ->firstOfMonth()
            ->format('Y-m-d');
        $employee_id = $this->me->employee_details->id;
        // Load all related payruns
        $payruns = \App\Payrun::where('payroll_date', '>=', $start)
            ->where('payroll_date', '<', $end)
            ->where('status', \App\Payrun::PAYRUN_STATUS_COMPLETED)
            ->get();
        // construct data
        $data = $util->combine_and_filter_payruns($payruns, $employee_id, true);
        // Log::debug($data);

        $date = Carbon::parse($request->input('id'))->format('F Y');
        $signatory = \App\Signatories::where('report_name', 'Payslips')->first();

        view()->share('vals', [
            'image' => "/images/logo.png",
            'data' => $data,
            'pay_date' =>  $date,
            'signatory' => $signatory
        ]);

        $pdf = PDF::loadView('pdf.payslip')->setPaper('letter', 'portrait');
        return $pdf->stream('payslip.pdf');
        return $pdf->download('payslip.pdf');
        return view('pdf.payslip');
    }

    public function getMyRange(Request $request) {
        $this->me = JWTAuth::parseToken()->authenticate();
        if (!$this->me->employee_details) {
            return response()->json(\App\Constants::ERROR_UNAUTHORIZED, 403);
        }
        return response()->json(
            [
                'start' => DB::table('payroll_history_for_all_employee')->where('employee_id', $this->me->employee_details->id)->min('payroll_period'),
                'end' => DB::table('payroll_history_for_all_employee')->where('employee_id', $this->me->employee_details->id)->max('payroll_period')
            ]
        );

    }


}
