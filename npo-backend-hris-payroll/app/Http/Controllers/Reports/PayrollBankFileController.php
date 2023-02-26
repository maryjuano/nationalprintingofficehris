<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PayrollBankFileController extends Controller
{
    public function getBankFile(Request $request, $id) {
        $unauthorized = $this->is_not_authorized(['bank_file']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $util = new \App\Http\Controllers\Reports\PayrollUtilController();
        $payrun = \App\Payrun::find($id);
        $account_numbers = \DB::table('employment_and_compensation')
            ->whereIn('employee_id', $payrun->employee_ids)
            ->select(
                'employee_id',
                'account_number',
            )
            ->get();

        if ($payrun->status < 1) {
            return response()->json(['error' => 'error', 'message' => 'Payrun (' . $payrun->payroll_name . ') is not simulated/finalized'], 400);
        }

        $data = $util->combine_and_filter_payruns([$payrun], null, false);
        $data = $util->array_to_object($data);
        $result = array();
        $total_net_pay = 0;
        foreach ($data as $item) {
            $item->employee_id;
            $middle_initial = empty($item->middle_name) ? ' ' : $item->middle_name[0];
            $account_number = $account_numbers->where('employee_id', $item->employee_id)->first()->account_number;
            $account_number_str = preg_replace("/[^0-9]/", "", $account_number);
            $account_number_str = str_pad($account_number_str, 10, '0', STR_PAD_LEFT);
            $last_name = strtoupper($item->last_name);
            $first_name = strtoupper($item->first_name);
            $middle_name = strtoupper($middle_initial);
            $complete_name = $last_name . ", " . $first_name . " " . $middle_name . ".";
            $left_side = $account_number_str . $complete_name;// preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities($last_name)) . ", " . preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities($first_name)) . " " . preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities($middle_name)) . ".";
            $left_data = $this->mb_str_pad($left_side, 50, ' ', STR_PAD_RIGHT);

            $last_digit = 18700000; // 32
            if(request('type') == 1) {
                $val = $item->net_1;
            } else if (request('type') == 2) {
                $val = $item->net_2;
            } else {
                $val = $item->net_pay;
            }

            $middle_digit = number_format($val, 2, '', '');
            $first_digit = str_pad($middle_digit, 15, '0', STR_PAD_LEFT);
            $right_data = $first_digit . $last_digit . '       ';

            $line = $left_data . $right_data . PHP_EOL;
            array_push($result, $line);
            $total_net_pay += $val;
        }
        // BANK FILE FOOTER
        $netPayStr = str_pad(number_format($total_net_pay, 2, '', ''), 15, '0', STR_PAD_LEFT);
        $totalEmployees = count($data);
        $totalEmployeesStr = str_pad(number_format($totalEmployees, 0, '', ''), 24, '0', STR_PAD_LEFT);
        $footer = '9999999999LANDBANK OF THE PHILIPPIN' . $netPayStr . $totalEmployeesStr . ' 00000' . PHP_EOL;
        array_push($result, $footer);

        $payroll_dates = $util->get_start_end_from_payrun($payrun, request('type'));

        $file_name = 'bank_file_' . $payroll_dates[0]->isoFormat('MMM_D') . '-' . $payroll_dates[1]->isoFormat('D_YYYY') . '.txt';
        Storage::disk('local')->put($file_name, $result);
        return response()->download(storage_path('app/' . $file_name), $file_name)->deleteFileAfterSend();

    }

    private function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT, $encoding = "UTF-8")
    {
        $diff = strlen($input) - mb_strlen($input, $encoding);
        return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
    }

}
