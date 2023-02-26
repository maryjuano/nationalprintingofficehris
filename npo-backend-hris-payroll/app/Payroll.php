<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Config;

class Payroll extends Model
{
    protected $table = 'payroll';

    public function exportPayrollRegistry()
    {
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () {
            $inputFileType = 'Xlsx';

            $inputFileName = './forms/Registry.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();

            $worksheet->getCell('A2')->setValue('John');
            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="registry.xlsx"');

        return $streamedResponse->send();
    }

    public function getAllEmployeeIDsInPayroll($startDate, $endDate)
    {
        \Log::info("Payroll StartDate" . $startDate);
        \Log::info("Payroll EndDate" . $endDate);

        $queryPayrollEmployeeIds = \DB::table('payroll')
            ->whereDate('startDate', '>=', $startDate)
            ->whereDate('endDate', '<=', $endDate)
            ->select('employeeId')
            ->groupBy('employeeId')
            ->get();

        return $queryPayrollEmployeeIds;
    }

    public function getAllPayrollsForEmployeeIds($startDate, $endDate, $employeeIds)
    {


        $queryPayrollEmployeeIds = \DB::table('payroll')
            ->whereDate('startDate', '>=', $startDate)
            ->whereDate('endDate', '<=', $endDate)
            ->whereIn('employeeId', $employeeIds)
            ->get();

        return $queryPayrollEmployeeIds;
    }

    public function calculateTotalPayroll($employeeId, $startDate, $endDate)
    {
        $queryPayrolls = \DB::table('payroll')
            ->whereDate('startDate', '>=', $startDate)
            ->whereDate('endDate', '<=', $endDate)
            ->where('employeeId', '=', $employeeId)
            ->get();

        $totalRemittance = 0;
        $total_net_taxable = 0;
        $total_withholding = 0;
        $total_mandatory = 0;
        foreach ($queryPayrolls as $payroll) {
            $payrollArray = json_decode($payroll->pay_structure, true);

            //\Log::info("calculateTotalPayroll id: " . $payroll->id . " net taxable: " . $payrollArray["net_taxable_pay"]);

            $total_net_taxable += $payrollArray["net_taxable_pay"];
            $total_withholding += $payrollArray["mandatoryDeduction"]["tax"];
            $total_mandatory  += $payrollArray["mandatoryDeduction"]["gsis"]["employee_share"] +
                $payrollArray["mandatoryDeduction"]["philhealth"]["employee_share"]
                + $payrollArray["mandatoryDeduction"]["pagibig"]["employee_share"];
        }

        $result["total_net_taxable"] = $total_net_taxable - $total_mandatory;
        $result["total_mandatory"] = $total_mandatory;
        $result["total_withholding"] = $total_withholding;

        return $result;
    }

    public function getPersonalInfo($employeeId)
    {
        $queryEmploymentCompensation = \DB::table('employment_and_compensation')
            ->where('employee_id', '=', $employeeId)
            ->first();

        $queryPersonalInfo = \DB::table('personal_information')
            ->where('employee_id', '=', $employeeId)
            ->first();

        if ($queryEmploymentCompensation && $queryPersonalInfo) {
            $result["tin"] = $queryEmploymentCompensation->tin;
            $result["last_name"] = $queryPersonalInfo->last_name;
            $result["first_name"] = $queryPersonalInfo->first_name;
            $result["middle_name"] = $queryPersonalInfo->middle_name;
            $result["address"] = $queryPersonalInfo->house_number . " " . $queryPersonalInfo->street
                . " " . $queryPersonalInfo->subdivision . " " . $queryPersonalInfo->barangay . " "
                . " " . $queryPersonalInfo->city . " " . $queryPersonalInfo->province;
            $result["zipcode"] = $queryPersonalInfo->zip_code;
            $result["birthday"] = $queryPersonalInfo->date_of_birth;
            $result["tel"] = $queryPersonalInfo->mobile_number;
        }

        return $result;
    }

    public function constructAlphaList($payroll, $worksheet, $alpha, $loop, $personal)
    {
        $worksheet->getCell($alpha["tin"] . $loop)->setValue($personal["tin"]);
        $worksheet->getCell($alpha["last_name"] . $loop)->setValue($personal["last_name"]);
        $worksheet->getCell($alpha["first_name"] . $loop)->setValue($personal["first_name"]);
        $worksheet->getCell($alpha["middle_name"] . $loop)->setValue($personal["middle_name"]);
        $worksheet->getCell($alpha["address"] . $loop)->setValue($personal["address"]);
        $worksheet->getCell($alpha["zipcode"] . $loop)->setValue($personal["zipcode"]);
        $worksheet->getCell($alpha["birthday"] . $loop)->setValue($personal["birthday"]);
        $worksheet->getCell($alpha["tel"] . $loop)->setValue($personal["tel"]);

        //$worksheet->getCell($alpha["net_taxable_pay"] . $loop)->setValue($payroll["total_net_taxable"]);
        $worksheet->getCell($alpha["net_withheld"] . $loop)->setValue($payroll["total_withholding"]);
        $worksheet->getCell($alpha["net_sss_philhealth_gsis_pagibig"] . $loop)->setValue($payroll["total_mandatory"]);

        $worksheet->getCell($alpha["net_taxable_pay"] . $loop)->setValue($payroll["total_net_taxable"]);


        return $worksheet;
    }

    public function exportAlphaList($type, $startDate, $endDate, $employeeIds)
    {
        $alphaListSettings = config('app.alphalist');
        //\Log::info("exportAlphaList settings: " . $alphaListSettings[0]["filename"]);

        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($alphaListSettings, $type, $startDate, $endDate, $employeeIds) {
            $inputFileType = $alphaListSettings[$type]["filetype"];

            $inputFileName = $alphaListSettings[$type]["filename"];

            $alpha = $alphaListSettings[$type];

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            //$reader->setLoadSheetsOnly("taxTable");

            $reader->setLoadSheetsOnly([$alphaListSettings[$type]["active_sheet"], "taxTable"]);

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            //$worksheet->getCell('A2')->setValue('John');
            //$allUniqueIds = $this->getAllEmployeeIDsInPayroll($startDate, $endDate, $employeeIds);
            //$payrolls = $this->getAllPayrollsForEmployeeIds($startDate, $endDate, $employeeIds);
            $loop = 2;
            foreach ($employeeIds as $employeeId) {
                $personal = $this->getPersonalInfo($employeeId);
                $payroll = $this->calculateTotalPayroll($employeeId, $startDate, $endDate);
                $worksheet = $this->constructAlphaList($payroll, $worksheet, $alpha, $loop, $personal);

                $loop++;
            }

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . $alphaListSettings[$type]["active_sheet"] . "_" . $startDate . "_" . $endDate . '.xlsx"');

        return $streamedResponse->send();
    }

    private function getPayslipComponents($payroll)
    {
        $queryEmployee = \DB::table('personal_information')
            ->where('employee_id', '=', $payroll->employeeId)
            ->first();

        if ($queryEmployee) {
            $result["first_name"] = $queryEmployee->first_name;
            $result["last_name"] = $queryEmployee->last_name;
        }
        return $result;
    }

    private function constructPayslip($queryPayroll, $spreadsheet)
    {
        $payrollData = $this->getPayslipComponents($queryPayroll);
        $clonedWorksheet = clone $spreadsheet->getSheetByName('Payslip');
        $clonedWorksheet->setTitle($payrollData["last_name"] . ", " . $payrollData["first_name"]);
        $clonedWorksheet->setCellValue(
            config('app.payslip.employee_name'),
            $payrollData["last_name"] . ", " . $payrollData["first_name"]
        );

        $rawPayroll = json_decode($queryPayroll->pay_structure, true);
        $clonedWorksheet->setCellValue(
            config('app.payslip.monthly_rate'),
            $rawPayroll["monthlyRate"]
        );

        $clonedWorksheet->setCellValue(
            config('app.payslip.dtr_deductions.late'),
            $rawPayroll["dtrDeduction"]["late"]
        );
        $clonedWorksheet->setCellValue(
            config('app.payslip.dtr_deductions.undertime'),
            $rawPayroll["dtrDeduction"]["undertime"]
        );
        $clonedWorksheet->setCellValue(
            config('app.payslip.dtr_deductions.absence'),
            $rawPayroll["dtrDeduction"]["absence"]
        );

        $clonedWorksheet->setCellValue(
            config('app.payslip.additional_pay.holiday'),
            $rawPayroll["additionalPay"]["holiday"]
        );
        $clonedWorksheet->setCellValue(
            config('app.payslip.additional_pay.night'),
            $rawPayroll["additionalPay"]["night"]
        );
        $clonedWorksheet->setCellValue(
            config('app.payslip.additional_pay.overtime'),
            $rawPayroll["additionalPay"]["overtime"]
        );
        $clonedWorksheet->setCellValue(
            config('app.payslip.additional_pay.rest'),
            $rawPayroll["additionalPay"]["rest"]
        );
        $clonedWorksheet->setCellValue(
            config('app.payslip.net_taxable_pay'),
            $rawPayroll["net_taxable_pay"]
        );
        $clonedWorksheet->setCellValue(
            config('app.payslip.mandatories.tax'),
            $rawPayroll["mandatoryDeduction"]["tax"]
        );

        $clonedWorksheet->setCellValue(
            config('app.payslip.mandatories.gsis'),
            $rawPayroll["mandatoryDeduction"]["gsis"]["employee_share"]
        );

        $clonedWorksheet->setCellValue(
            config('app.payslip.mandatories.philhealth'),
            $rawPayroll["mandatoryDeduction"]["philhealth"]["employee_share"]
        );

        $clonedWorksheet->setCellValue(
            config('app.payslip.mandatories.pagibig'),
            $rawPayroll["mandatoryDeduction"]["pagibig"]["employee_share"]
        );
        return $clonedWorksheet;
    }

    public function generatePayslip($startDate, $endDate, $employeeId)
    {


        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($employeeId, $startDate, $endDate) {
            $inputFileType = 'Xlsx';

            $inputFileName = './forms/Payslip.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setLoadSheetsOnly(["Payslip"]);
            $spreadsheet = $reader->load($inputFileName);


            if ($employeeId >= 0) {
                \Log::info("generatePayslip if valid employeeId: " . $employeeId);

                $queryPayroll = \DB::table('payroll')
                    ->where('status', '=', 0)
                    ->whereDate('startDate', '=', $startDate)
                    ->whereDate('endDate', '=', $endDate)
                    ->where('employeeId', '=', $employeeId)

                    ->first();
                if ($queryPayroll) {

                    $clonedWorksheet = $this->constructPayslip($queryPayroll, $spreadsheet);




                    $spreadsheet->addSheet($clonedWorksheet);
                }
            } else {
                \Log::info("generatePayslip else employeeId: " . $employeeId);

                $queryPayroll = \DB::table('payroll')
                    ->where('status', '=', 0)
                    ->whereDate('startDate', '=', $startDate)
                    ->whereDate('endDate', '=', $endDate)
                    ->get();


                foreach ($queryPayroll as $payroll) {
                    \Log::info("generatePayslip employeeId: " . $payroll->employeeId);

                    $clonedWorksheet = $this->constructPayslip($payroll, $spreadsheet);




                    $spreadsheet->addSheet($clonedWorksheet);
                }
            }





            //$worksheet->getCell('A2')->setValue('John');
            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="payslip' . $startDate . '_' . $endDate . '.xlsx"');

        return $streamedResponse->send();
    }



    public function getTimeDataMultipliers()
    {
        $queryNightDiff = \DB::table('time_data')
            ->where('time_data_name', '=', "Night Differential")
            ->first();

        $result["nightDiffMultiplier"] = $queryNightDiff ? $queryNightDiff->multiplier : 0;

        $queryRestDay = \DB::table('time_data')
            ->where('time_data_name', '=', "Rest")
            ->first();

        $result["restDayMultiplier"] = $queryRestDay ? $queryRestDay->multiplier : 0;
        $result["restOvertimeMultiplier"] = $queryRestDay ? $queryRestDay->multiplierOT : 0;

        $queryRegularDay = \DB::table('time_data')
            ->where('time_data_name', '=', "Regular")
            ->first();

        $result["regularOvertimeMultiplier"] = $queryRestDay ? $queryRegularDay->multiplierOT : 0;

        return $result;
    }

    public function getMonthlyRate($employeeId, $startDate)
    {
        $queryCompensation = \DB::table('employment_and_compensation')
            ->leftJoin('salaries', 'salaries.grade', '=', 'employment_and_compensation.salary_grade_id')
            // ->whereDate('salaries.effectivity_date', '<=', $startDate) TO DO needed validation for effectivity date
            ->where('employment_and_compensation.employee_id', '=', $employeeId)
            ->where('salaries.status', '=', 1)
            ->select('employment_and_compensation.step_increment', 'salaries.step')
            ->first();

        if ($queryCompensation) {
            $step = $queryCompensation->step_increment;
            $salarySteps = json_decode($queryCompensation->step);

            // return $salarySteps[$step-1];
            return $salarySteps[$step];
        } else {
            return 0;
        }
    }

    public function calculateGSIS($monthlyRate)
    {
        $queryGSIS = \DB::table('gsis')
            ->where('status', '=', 1)
            ->first();

        if ($queryGSIS) {
            $result["employee_share"] = $queryGSIS->personal_share * $monthlyRate;
            $result["employer_share"] = $queryGSIS->government_share * $monthlyRate;
        } else {
            $result["employee_share"] = 0;
            $result["employer_share"] = 0;
        }

        return $result;
    }

    public function calculateTax($monthlyRate)
    {
        $annualRate = $monthlyRate * 12;
        $queryTax = \DB::table('tax')
            ->where('lowerLimit', '<', $annualRate)
            ->where('upperLimit', '>=', $annualRate)
            ->where('isActive', '<=', 1)
            ->first();

        if ($queryTax) {
            $tax = ($annualRate - $queryTax->lowerLimit) * ($queryTax->percentage / 100) + $queryTax->constant;
        } else {
            $tax = 0;
        }

        return round($tax / 12, 2);
    }



    public function calculatePhilHealth($monthlyRate)
    {
        $queryPhilHealth = \DB::table('philhealth')
            ->where('minimum_range', '<=', $monthlyRate)
            ->where('maximum_range', '>=', $monthlyRate)
            ->first();

        $result["employee_share"] = $queryPhilHealth ? json_decode($queryPhilHealth->personal_share)[0] : 0;
        $result["employer_share"] = $queryPhilHealth ? json_decode($queryPhilHealth->government_share)[0] : 0;
        $result["monthly_premium"] = $queryPhilHealth ? json_decode($queryPhilHealth->monthly_premium)[0] : 0;


        return $result;
    }

    public function calculatePagibig($monthlyRate)
    {
        $queryPagibig = \DB::table('pagibig')
            ->where('minimum_range', '<=', $monthlyRate)
            ->where('maximum_range', '>=', $monthlyRate)
            ->first();


        $result["employee_share"] = $queryPagibig ? $queryPagibig->personal_share : 0;
        $result["employer_share"] = $queryPagibig ? $queryPagibig->government_share : 0;

        return $result;
    }
}
