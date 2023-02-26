<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RemittanceController extends Controller
{
    public function list_employees(Request $request) {
        // list all employees with their details
        $unauthorized = $this->is_not_authorized(['view_remittance']);
        if ($unauthorized) {
            return $unauthorized;
        }


    }

    public function gsis(Request $request) {
        // TODO
    }

    public function philhealth(Request $request) {
        // TODO
    }

    public function taxes(Request $request) {
        // TODO
    }

    public function pagibig(Request $request) {
        // TODO
    }

    private function derive_start_end($type, $year, $month) {
        // TODO
    }
}
