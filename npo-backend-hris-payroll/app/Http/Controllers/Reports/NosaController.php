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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Validator;

class NosaController extends Controller
{
    public function read(Request $request, $id)
    {
        $unauthorized = $this->is_not_authorized(['nosa']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'circular' => 'required|string',
            'circular_date' => 'required|string',
            'executive_order' => 'required|string',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $nosa_extra_data = \App\ReportsExtraData::where('report', 'nosa')->first();
        $nosa_extra_data->data = $request->only('circular', 'circular_date', 'executive_order');
        $nosa_extra_data->save();

        $nosas = \App\NoticeOfSalaryAdjustment::where('employee_id', $id)->orderBy('effectivity_date', 'desc')->limit(1)->get();
        view()->share('nosas', $nosas);
        $signatory = \App\Signatories::where('report_name', 'NOSA')->first();
        view()->share('signatory', $signatory);
        view()->share('generated_date', Carbon::now());
        view()->share('extra_data', $nosa_extra_data->data);
        $pdf = PDF::loadView('pdf.nosa')->setPaper('letter', 'portrait');
        return $pdf->stream('nosa.pdf');
        // return $pdf->download('nosa.pdf');
        return view('pdf.nosa');
    }

    public function list()
    {

        $unauthorized = $this->is_not_authorized(['nosa']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $query = \App\NoticeOfSalaryAdjustment::select('*');

        if (request('start_date')) {
            $query = $query->where('effectivity_date', '>=', request('start_date'));
        }
        if (request('end_date')) {
            $query = $query->where('effectivity_date', '<=', request('end_date'));
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

        $unauthorized = $this->is_not_authorized(['nosa']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator_arr = [
            'ids' => 'required',
            'circular' => 'required|string',
            'circular_date' => 'required|string',
            'executive_order' => 'required|string',
        ];
        $validator = Validator::make($request->all(), $validator_arr);
        if ($validator->fails()) {
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()->all()], 400);
        }

        $nosa_extra_data = \App\ReportsExtraData::where('report', 'nosa')->first();
        $nosa_extra_data->data = $request->only('circular', 'circular_date', 'executive_order');
        $nosa_extra_data->save();

        $nosas = \App\NoticeOfSalaryAdjustment::whereIn('id', request('ids'))->orderBy('effectivity_date', 'desc')->get();
        view()->share('nosas', $nosas);
        $signatory = \App\Signatories::where('report_name', 'NOSA')->first();
        view()->share('signatory', $signatory);
        view()->share('generated_date', Carbon::now());
        view()->share('extra_data', $nosa_extra_data->data);
        $pdf = PDF::loadView('pdf.nosa')->setPaper('letter', 'portrait');
        // return $pdf->stream('nosa.pdf');
        return $pdf->download('nosa.pdf');
        // return view('pdf.nosa');
    }

    public function getTable()
    {

        $unauthorized = $this->is_not_authorized(['nosa']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $nosas = \App\NoticeOfSalaryAdjustment::whereIn('id', request('ids'))->orderBy('effectivity_date', 'desc')->get();

        $streamedResponse = new StreamedResponse();

        $streamedResponse->setCallback(function () use ($nosas) {
            $inputFileType = 'Xlsx';
            $inputFileName = './forms/reports/nosa_table.xlsx';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $reader->setLoadSheetsOnly('Sheet1');

            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $row = 2; //row start
            $worksheet->insertNewRowBefore($row, COUNT($nosas));

            $worksheet->getStyle('A1' . ':K1')->applyFromArray($this->headerStyle());
            $worksheet->getColumnDimension('A')->setWidth('30', 'pt');
            $worksheet->getColumnDimension('B')->setWidth('30', 'pt');
            $worksheet->getColumnDimension('C')->setWidth('10', 'pt');
            $worksheet->getColumnDimension('D')->setWidth('10', 'pt');
            $worksheet->getColumnDimension('E')->setWidth('30', 'pt');
            $worksheet->getColumnDimension('F')->setWidth('10', 'pt');
            $worksheet->getColumnDimension('G')->setWidth('10', 'pt');
            $worksheet->getColumnDimension('H')->setWidth('12', 'pt');
            $worksheet->getColumnDimension('I')->setWidth('12', 'pt');
            $worksheet->getColumnDimension('J')->setWidth('12', 'pt');
            $worksheet->getColumnDimension('K')->setWidth('15', 'pt');

            $worksheet->getStyle('H')->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('I')->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('J')->getNumberFormat()->setFormatCode('#,##0.00');

            foreach ($nosas as $item) {
                $worksheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($this->BoxStyle());
                $worksheet->getCell('A' . $row)->setValue($item->employee->name);
                $worksheet->getCell('B' . $row)->setValue($item->old_position->position_name);
                $worksheet->getCell('C' . $row)->setValue($item->old_grade);
                $worksheet->getCell('D' . $row)->setValue($item->old_step);
                $worksheet->getCell('E' . $row)->setValue($item->new_position->position_name);
                $worksheet->getCell('F' . $row)->setValue($item->new_grade);
                $worksheet->getCell('G' . $row)->setValue($item->new_step);
                $worksheet->getCell('H' . $row)->setValue($item->old_rate);
                $worksheet->getCell('I' . $row)->setValue($item->new_rate);
                $worksheet->getCell('J' . $row)->setValue($item->new_rate - $item->old_rate);
                $worksheet->getCell('K' . $row)->setValue($item->effectivity_date->isoFormat('MM/DD/YYYY'));
                $row++;
            }

            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        });

        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'nosa_table' . '.xlsx"');

        return $streamedResponse;
    }

    public function getNosaExtraData(Request $request) {
        $unauthorized = $this->is_not_authorized(['nosa']);
        if ($unauthorized) {
            return $unauthorized;
        }

        $nosa_extra_data = \App\ReportsExtraData::where('report', 'nosa')->first();
        return response()->json($nosa_extra_data->data);
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
}
