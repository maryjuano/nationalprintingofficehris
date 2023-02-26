<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use PDF;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Validator;

class NosiController extends Controller
{
    public function read($id)
    {
        $unauthorized = $this->is_not_authorized(['nosi']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $nosis = \App\NoticeOfStepIncrement::where('employee_id', $id)->orderBy('effectivity_date', 'desc')->limit(1)->get();
        view()->share('nosis', $nosis);
        $signatory = \App\Signatories::where('report_name', 'NOSI')->first();
        view()->share('signatory', $signatory);
        view()->share('generated_date', Carbon::now());
        $pdf = PDF::loadView('pdf.nosi')->setPaper('letter', 'portrait');
        return $pdf->stream('nosi.pdf');
        // return $pdf->download('nosi.pdf');
        return view('pdf.nosi');
    }

    public function list(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['nosi']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'month' => 'sometimes|integer|between:0,11',
            'year' => 'sometimes|date_format:Y'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $query = \App\NoticeOfStepIncrement::query();

        if ($request->filled('month') && $request->filled('year')) {
            $startTime = Carbon::create(
                $request->input('year'),
                $request->input('month') + 1,
                1,
            )->startOfDay();
            $endTime = $startTime->copy()->endOfMonth()->endOfDay();
            $query = $query->whereBetween('effectivity_date', [$startTime->toDateTimeString(), $endTime->toDateTimeString()]);
        }

        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);
    }

    public function getPdfs(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['nosi']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'month' => 'sometimes|integer|between:0,11',
            'year' => 'sometimes|date_format:Y'
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $query = \App\NoticeOfStepIncrement::whereIn('id', request('ids'))->orderBy('effectivity_date', 'desc');
        
        if ($request->filled('month') && $request->filled('year')) {
            $startTime = Carbon::create(
                $request->input('year'),
                $request->input('month') + 1,
                1,
            )->startOfDay();
            $endTime = $startTime->copy()->endOfMonth()->endOfDay();
            $query->whereBetween('effectivity_date', [$startTime->toDateTimeString(), $endTime->toDateTimeString()]);
        }
        
        $nosis = $query->get();
        view()->share('nosis', $nosis);
        $signatory = \App\Signatories::where('report_name', 'NOSI')->first();
        view()->share('signatory', $signatory);
        view()->share('generated_date', Carbon::now());
        $pdf = PDF::loadView('pdf.nosi')->setPaper('letter', 'portrait');
        return $pdf->stream('nosi.pdf');
        // return $pdf->download('nosi.pdf');
        return view('pdf.nosi');
    }

    // public function getTable()
    // {

    //     $unauthorized = $this->is_not_authorized(['nosi']);
    //     if ($unauthorized) {
    //         return $unauthorized;
    //     }

    //     $nosis = \App\NoticeOfStepIncrement::whereIn('id', request('ids'))->orderBy('effectivity_date', 'desc')->get();

    //     $streamedResponse = new StreamedResponse();

    //     $streamedResponse->setCallback(function () use ($nosis) {
    //         $inputFileType = 'Xlsx';
    //         $inputFileName = './forms/reports/nosi_table.xlsx';

    //         $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

    //         $reader->setLoadSheetsOnly('Sheet1');

    //         $spreadsheet = $reader->load($inputFileName);
    //         $worksheet = $spreadsheet->getActiveSheet();
    //         $row = 2; //row start
    //         $worksheet->insertNewRowBefore($row, COUNT($nosis));
    //         //$worksheet->getStyle('A' . 1 . ':F' . 1)->applyFromArray($this->headerStyle());
    //         $worksheet->getStyleByColumnAndRow(1,1,6,1)->applyFromArray($this->headerStyle());

    //         $worksheet->getColumnDimension('A')->setWidth('30', 'pt');
    //         $worksheet->getColumnDimension('B')->setWidth('30', 'pt');
    //         $worksheet->getColumnDimension('C')->setWidth('12', 'pt');
    //         $worksheet->getColumnDimension('D')->setWidth('12', 'pt');
    //         $worksheet->getColumnDimension('E')->setWidth('12', 'pt');
    //         $worksheet->getColumnDimension('F')->setWidth('15', 'pt');

    //         $worksheet->getStyle('C')->getNumberFormat()->setFormatCode('#,##0.00');
    //         $worksheet->getStyle('D')->getNumberFormat()->setFormatCode('#,##0.00');
    //         $worksheet->getStyle('E')->getNumberFormat()->setFormatCode('#,##0.00');

    //         foreach ($nosis as $item) {
    //             $worksheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($this->BoxStyle());
    //             $worksheet->getCell('A' . $row)->setValue($item->employee->name);
    //             $worksheet->getCell('B' . $row)->setValue($item->position->position_name);
    //             $worksheet->getCell('C' . $row)->setValue($item->old_rate);
    //             $worksheet->getCell('D' . $row)->setValue($item->new_rate);
    //             $worksheet->getCell('E' . $row)->setValue($item->new_rate - $item->old_rate);
    //             $worksheet->getCell('F' . $row)->setValue($item->effectivity_date->isoFormat('MM/DD/YYYY'));
    //             $row++;
    //         }

    //         $writer =  new Xlsx($spreadsheet);
    //         $writer->save('php://output');
    //         die;
    //     });

    //     $streamedResponse->setStatusCode(Response::HTTP_OK);
    //     $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    //     $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'nosi_table' . '.xlsx"');

    //     return $streamedResponse;
    // }

    public function getTable(Request $request)
    {
        $unauthorized = $this->is_not_authorized(['nosi']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\NoticeOfStepIncrement::whereIn('id', request('ids'))->orderBy('effectivity_date', 'desc');
        
        if ($request->filled('month') && $request->filled('year')) {
            $startTime = Carbon::create(
                $request->input('year'),
                $request->input('month') + 1,
                1,
            )->startOfDay();
            $endTime = $startTime->copy()->endOfMonth()->endOfDay();
            $query->whereBetween('effectivity_date', [$startTime->toDateTimeString(), $endTime->toDateTimeString()]);
        }
        
        $nosis = $query->get();

        switch ($request->input('month')) {
            case 0:
            case 1:
            case 2:
                $quarter = "- 1ST QUARTER";
                break;
            case 3:
            case 4:
            case 5:
                $quarter = "- 2ND QUARTER";
                break;
            case 6:
            case "7":
            case 8:
                $quarter = "- 3RD QUARTER";
                break;
            case 9:
            case 10:
            case 11:
                $quarter = "- 4TH QUARTER";
                break;
            default:
                $quarter = "";
        }

        $months = [
            'JANUARY',
            'FEBRUARY',
            'MARCH',
            'APRIL',
            'MAY',
            'JUNE',
            'JULY',
            'AUGUST',
            'SEPTEMBER',
            'OCTOBER',
            'NOVEMBER',
            'DDECEMBER'
        ];

        $streamedResponse = new StreamedResponse();
        $signatories = \App\Signatories::where('report_name', 'NOSI_XLS')->first();

        $streamedResponse->setCallback(function () use ($nosis, $request, $months, $quarter, $signatories) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/reports/nosi2_table.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $reader->setLoadSheetsOnly('Sheet1');

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $row = 8; //row start
            $worksheet->insertNewRowBefore($row, COUNT($nosis));
            
            $worksheet->getStyle('A7:I7')->applyFromArray($this->headerStyle());
            $worksheet->getColumnDimension('A')->setWidth('30', 'pt');
            $worksheet->getColumnDimension('B')->setWidth('30', 'pt');
            $worksheet->getColumnDimension('C')->setWidth('10', 'pt');
            $worksheet->getColumnDimension('D')->setWidth('10', 'pt');
            $worksheet->getColumnDimension('E')->setWidth('10', 'pt');
            $worksheet->getColumnDimension('F')->setWidth('12', 'pt');
            $worksheet->getColumnDimension('G')->setWidth('12', 'pt');
            $worksheet->getColumnDimension('H')->setWidth('12', 'pt');
            $worksheet->getColumnDimension('I')->setWidth('15', 'pt');

            $worksheet->getStyle('F')->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('G')->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('H')->getNumberFormat()->setFormatCode('#,##0.00');

            $year = $request->input('year');
            $worksheet->getCell('A' . 3)->setValue("FY $year $quarter");
            $worksheet->getCell('A' . 4)->setValue('MONTH OF '. $months[$request->input('month')]);

            foreach ($nosis as $item) {
                $worksheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($this->BoxStyle());
                $worksheet->getCell('A' . $row)->setValue($item->employee->name);
                $worksheet->getCell('B' . $row)->setValue($item->position->position_name);
                $worksheet->getCell('C' . $row)->setValue($item->grade);
                $worksheet->getCell('D' . $row)->setValue($item->old_step);
                $worksheet->getCell('E' . $row)->setValue($item->new_step);
                $worksheet->getCell('F' . $row)->setValue($item->old_rate);
                $worksheet->getCell('G' . $row)->setValue($item->new_rate);
                $worksheet->getCell('H' . $row)->setValue($item->new_rate - $item->old_rate);
                $worksheet->getCell('I' . $row)->setValue($item->effectivity_date->isoFormat('MM/DD/YYYY'));
                $row++;
            }

            $row++;
            $worksheet->getCell('A' . $row)->setValue('Certified Correct:');
            $row++;
            $worksheet->getStyle('H' . $row)->getAlignment()->setHorizontal('right');
            $worksheet->getCell('H' . $row)->setValue('Payroll:');
            $worksheet->getCell('I' . $row)->setValue('_______________');
            $row++;
            $worksheet->getStyle('A' . $row)->getFont()->setUnderline(true);
            $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal('center');
            $worksheet->getCell('A' . $row)->setValue($signatories->signatories[0]['name']);
            $spreadsheet->getActiveSheet()->mergeCells("C$row:E$row");
            $worksheet->getStyle('C' . $row)->getFont()->setUnderline(true);
            $worksheet->getStyle('C' . $row)->getAlignment()->setHorizontal('center');
            $worksheet->getCell('C' . $row)->setValue($signatories->signatories[1]['name']);
            $row++;
            $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal('center');
            $worksheet->getCell('A' . $row)->setValue($signatories->signatories[0]['title']);
            $spreadsheet->getActiveSheet()->mergeCells("C$row:E$row");
            $worksheet->getStyle('C' . $row)->getAlignment()->setHorizontal('center');
            $worksheet->getCell('C' . $row)->setValue($signatories->signatories[1]['title']);
            $worksheet->getStyle('H' . $row)->getAlignment()->setHorizontal('right');
            $worksheet->getCell('H' . $row)->setValue('FMD:');
            $worksheet->getCell('I' . $row)->setValue('_______________');

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'nosi_table' . '.xlsx"');

        return $streamedResponse;
    }

    private function BoxStyle()
    {
        return [
            'font' => [
                'size' => 10,
                'bold' => false
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
    }
    private function headerStyle() {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => array('argb' => 'fffff2cc')
            ]
        ];
    }

    public function candidates() {
        $unauthorized = $this->is_not_authorized(['nosi']);
        if ($unauthorized) {
            return $unauthorized;
        }
        $now = Carbon::now()->subYears(3);
        $query = \DB::table('employees')
            ->join('personal_information', 'employees.id', '=', 'personal_information.employee_id')
            ->join('employment_and_compensation', 'employment_and_compensation.employee_id', '=', 'employees.id')
            ->join('positions', 'employment_and_compensation.position_id', '=', 'positions.id')
            ->where('job_info_effectivity_date', '<', $now->format('Y-m-d'))
            ->where('step_increment', '<', 7)
            ->select('personal_information.*', 'positions.position_name', 'step_increment', 'job_info_effectivity_date');
        $ALLOWED_FILTERS = [];
        $SEARCH_FIELDS = [];
        $JSON_FIELDS = [];
        $BOOL_FIELDS = [];
        $response = $this->paginate_filter_sort_search($query, $ALLOWED_FILTERS, $JSON_FIELDS, $BOOL_FIELDS, $SEARCH_FIELDS);

        return response()->json($response);    }

}
