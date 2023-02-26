<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PDF;
use Carbon\Carbon;

class CoecController extends Controller
{
    public function empCert($id)
    {
        $unauthorized = $this->is_not_authorized(['coec_coe']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $employee = \App\Employee::findOrFail($id);
        $suffix = $employee->personal_information->ext == 'NA' ? '' : ' ' . $employee->personal_information->ext;
        $salaries = [];


        $total = 0;
        foreach(request('earnings') as $earning) {
            $earning = json_decode($earning);
            $salaries[] = [
                'label' => $earning->title,
                'amount' => number_format($earning->amount, 2)
            ];
            $total = $total + $earning->amount;
        }
        $employee_certificate = (object) array(
            'last_name' => Str::title($employee->personal_information->last_name),
            'name' => $employee->personal_information->first_name . ' ' . $employee->personal_information->middle_name . ' ' . $employee->personal_information->last_name . $suffix,
            'job_start' => Carbon::createFromFormat('Y-m-d', $employee->employment_and_compensation->date_hired)->format('F d, Y'),
            'position_name' => $employee->employment_and_compensation->position->position_name,
            'salaries' => $salaries,
            'total' => number_format($total,2),
            'gender' => $employee->personal_information->gender
        );

        $signatory = \App\Signatories::where('report_name', 'COEC')->first();

        $logo  = public_path() . '/images/logo.png';
        $header  = public_path() . '/images/certificate_header.png';
        $footer  = public_path() . '/images/certificate_footer.png';
        view()->share('logo', $logo);
        view()->share('header', $header);
        view()->share('footer', $footer);
        view()->share('employee', $employee_certificate);
        view()->share('signatory', $signatory);
        $pdf = PDF::loadView('pdf.empCert2')->setPaper('letter', 'portrait');
        return $pdf->stream('empCert2.pdf');
    }

    public function get_stub($id) {
        $unauthorized = $this->is_not_authorized(['coec_coe']);
        if ($unauthorized) {
            return $unauthorized;
        }

    }

    public function coe(\App\Employee $employee)
    {
        $unauthorized = $this->is_not_authorized(['coec_coe']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $suffix = $employee->personal_information->ext == 'NA' ? '' : ' ' . $employee->personal_information->ext;
        $employee_certificate = (object) array(
            'last_name' => Str::title($employee->personal_information->last_name),
            'name' => $employee->personal_information->first_name . ' ' . $employee->personal_information->middle_name . ' ' . $employee->personal_information->last_name . $suffix,
            'job_start' => Carbon::createFromFormat('Y-m-d', $employee->employment_and_compensation->date_hired)->format('F d, Y'),
            'position_name' => $employee->employment_and_compensation->position->position_name,
            'gender' => $employee->personal_information->gender
        );

        $signatory = \App\Signatories::where('report_name', 'COEC')->first();

        $logo  = public_path() . '/images/logo.png';
        $header  = public_path() . '/images/certificate_header.png';
        $footer  = public_path() . '/images/certificate_footer.png';
        view()->share('logo', $logo);
        view()->share('header', $header);
        view()->share('footer', $footer);
        view()->share('employee', $employee_certificate);
        view()->share('signatory', $signatory);
        $pdf = PDF::loadView('pdf.coe')->setPaper('letter', 'portrait');
        return $pdf->stream('coe.pdf');
    }
}
