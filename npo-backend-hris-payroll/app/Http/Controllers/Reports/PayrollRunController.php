<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;

class PayrollRunController extends Controller
{
    const DTR_DEDUCTIONS = [
        \App\Adjustment::CONST_LATE,
        \App\Adjustment::CONST_UNDERTIME,
        \App\Adjustment::CONST_ABSENCE
    ];
    const OVERTIME = [\App\Adjustment::CONST_OVERTIME];

    public function getReport(Request $request, $id)
    {
        // $unauthorized = $this->is_not_authorized(['payroll_registry']);
        // if ($unauthorized) {
        //     return $unauthorized;
        // }

        $reports = request('reports');

        $filename = 'report.xlsx';
        if (sizeof($reports) == 1) {
            $filename = $reports[0] . '.xlsx';
        }

        // create the file and add each worksheet
        // https://stackoverflow.com/questions/50250387/merge-in-excel-with-phpspreadsheet
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($id, $reports, $request) {
            $TEMPLATES = array(
                'MasterList' => './forms/Payroll/empty.xlsx',
                'Overtime' => './forms/Payroll/ot_report.xlsx',

                'Registry' => './forms/Payroll_Registry.xlsx',
                'PayrollRep' => './forms/Payroll/PRreg15.xlsx',
                'CAreg' => './forms/Payroll/ca-new.xlsx',
                'CAregAttach' => './forms/Payroll/ca-attach-new.xlsx',
                'DV' => './forms/Payroll/dv-3.xlsx',
                'BUR' => './forms/Payroll/BUR.xlsx',

                'Registry_1' => './forms/Payroll_Registry.xlsx',
                'PayrollRep_1' => './forms/Payroll/PRreg15.xlsx',
                'CAreg_1' => './forms/Payroll/ca-new.xlsx',
                'CAregAttach_1' => './forms/Payroll/ca-attach-new.xlsx',
                'DV_1' => './forms/Payroll/dv-3.xlsx',
                'BUR_1' => './forms/Payroll/BUR.xlsx',

                'Registry_2' => './forms/Payroll_Registry.xlsx',
                'PayrollRep_2' => './forms/Payroll/PRreg15.xlsx',
                'CAreg_2' => './forms/Payroll/ca-new.xlsx',
                'CAregAttach_2' => './forms/Payroll/ca-attach-new.xlsx',
                'DV_2' => './forms/Payroll/dv-3.xlsx',
                'BUR_2' => './forms/Payroll/BUR.xlsx',

                'dummy' => './forms/Payroll/DV.xlsx'

            );

            $mainReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            // $reader->setLoadSheetsOnly(['Sheet1', 'sheet1']); // assume each template file only has 1 sheet
            $spreadsheetMain = $mainReader->load($TEMPLATES['DV_1']);
            // $spreadsheetMain = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            // Log::debug($spreadsheetMain->getSheetNames());
            $util = new \App\Http\Controllers\Reports\PayrollUtilController();
            $payrun = \App\Payrun::with([
                'createdBy',
                'positionName'
            ])->find($id);
            if($payrun->run_type != \App\Http\Controllers\PayrollRunController::RUN_TYPE_REGULAR) {
                $organizedPayrunData = $util->organize_payrun_for_reports([$payrun], $util::REPORT_TYPE_FULL);
            }
            else {
                $organizedPayrunData_1 = $util->organize_payrun_for_reports([$payrun], $util::REPORT_TYPE_FIRST);
                $organizedPayrunData_2 = $util->organize_payrun_for_reports([$payrun], $util::REPORT_TYPE_SECOND);
            }
            foreach ($reports as $report) {
                // load sheet from template
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');

                $spreadsheet = $reader->load($TEMPLATES[$report]);
                // Log::debug($spreadsheet->getSheetNames());
                $worksheet = $spreadsheet->getActiveSheet();
                $worksheet->setTitle($report);

                // prepare data and write to worksheet
                switch ($report) {
                    case 'DV':
                        $DVController = new PayrollRunReportDVController();
                        $DVController->generateDVSheet($payrun, $organizedPayrunData, $worksheet);
                        break;
                    case 'DV_1':
                        $DVController = new PayrollRunReportDVController();
                        $DVController->generateDVSheet($payrun, $organizedPayrunData_1, $worksheet);
                        break;
                    case 'DV_2':
                        $DVController = new PayrollRunReportDVController();
                        $DVController->generateDVSheet($payrun, $organizedPayrunData_2, $worksheet);
                        break;
                    case 'BUR':
                        $BURController = new PayrollRunReportBURController();
                        $BURController->generateBURSheet($payrun, $organizedPayrunData, $worksheet);
                        break;
                    case 'BUR_1':
                        $BURController = new PayrollRunReportBURController();
                        $BURController->generateBURSheet($payrun, $organizedPayrunData_1, $worksheet);
                        break;
                    case 'BUR_2':
                        $BURController = new PayrollRunReportBURController();
                        $BURController->generateBURSheet($payrun, $organizedPayrunData_2, $worksheet);
                        break;
                    case 'CAreg':
                        $CAController = new PayrollRunReportCAController();
                        $CAController->generateCASheet($payrun, $organizedPayrunData, $worksheet);
                        break;
                    case 'CAreg_1':
                        $CAController = new PayrollRunReportCAController();
                        $CAController->generateCASheet($payrun, $organizedPayrunData_1, $worksheet);
                        break;
                    case 'CAreg_2':
                        $CAController = new PayrollRunReportCAController();
                        $CAController->generateCASheet($payrun, $organizedPayrunData_2, $worksheet);
                        break;
                    case 'CAregAttach':
                        $CAController = new PayrollRunReportCAController();
                        $CAController->generateCAAttachSheet($payrun, $organizedPayrunData, $worksheet);
                        break;
                    case 'CAregAttach_1':
                        $CAController = new PayrollRunReportCAController();
                        $CAController->generateCAAttachSheet($payrun, $organizedPayrunData_1, $worksheet);
                        break;
                    case 'CAregAttach_2':
                        $CAController = new PayrollRunReportCAController();
                        $CAController->generateCAAttachSheet($payrun, $organizedPayrunData_2, $worksheet);
                        break;
                    case 'PayrollRep':
                        $PRController = new PayrollRunReportPRController();
                        $PRController->generatePRSheet($payrun, $organizedPayrunData, $worksheet);
                        break;
                    case 'PayrollRep_1':
                        $PRController = new PayrollRunReportPRController();
                        $PRController->generatePRSheet($payrun, $organizedPayrunData_1, $worksheet);
                        break;
                    case 'PayrollRep_2':
                        $PRController = new PayrollRunReportPRController();
                        $PRController->generatePRSheet($payrun, $organizedPayrunData_2, $worksheet);
                        break;
                    case 'Registry':
                        $registryController = new \App\Http\Controllers\Reports\PayrollRegistryController();
                        $registry = $registryController->organizeRegistryData($id, $util::REPORT_TYPE_FULL);
                        $signatories = \App\Signatories::where('report_name', 'Payroll Registry')->first();
                        $registryController->generateRegistrySheet($registry, $signatories, $worksheet);
                        break;
                    case 'Registry_1':
                        $registryController = new \App\Http\Controllers\Reports\PayrollRegistryController();
                        $registry_1 = $registryController->organizeRegistryData($id, $util::REPORT_TYPE_FIRST);
                        $signatories = \App\Signatories::where('report_name', 'Payroll Registry')->first();
                        $registryController->generateRegistrySheet($registry_1, $signatories, $worksheet);
                        break;
                    case 'Registry_2':
                        $registryController = new \App\Http\Controllers\Reports\PayrollRegistryController();
                        $registry_1 = $registryController->organizeRegistryData($id, $util::REPORT_TYPE_SECOND);
                        $signatories = \App\Signatories::where('report_name', 'Payroll Registry')->first();
                        $registryController->generateRegistrySheet($registry_1, $signatories, $worksheet);
                        break;
                    case 'MasterList':
                        $reportController = new \App\Http\Controllers\Reports\PayrollRunReportController();
                        $reportController->generateMasterList($id, $worksheet);
                        break;
                    case 'Overtime':
                        $reportController = new \App\Http\Controllers\Reports\PayrollRunReportController();
                        $reportController->generateOt($id, $worksheet);
                        break;
                }
                // add spreadsheet
                $spreadsheetMain->addExternalSheet($worksheet);
                Log::debug($report . ':' . memory_get_usage());
                unset($worksheet);
                unset($spreadsheet);
            }

            $spreadsheetMain->removeSheetByIndex(0);
            Log::debug('Before writer:' . memory_get_usage());
            $writer =  new Xlsx($spreadsheetMain);
            Log::debug('After writer:' . memory_get_usage());
            $writer->save('php://output');
            die;
        });
        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $streamedResponse;
    }

}
