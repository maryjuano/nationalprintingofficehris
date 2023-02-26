<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class Contribution extends Model
{
  protected $table = "contribution_request";
  protected $fillable = [];
  protected $casts = [
    'amount' => 'float'
  ];

  private function query_pagibig_contribution_requests($cont_type, $employee_id, $fromPagIbigTable, $date_request = null)
  {
    $contReq = \App\Contribution::select('*')
      ->where('employee_id', $employee_id)
      ->where('status', 1)
      ->where('contribution_type', $cont_type)
      ->orderBy('updated_at', 'DESC');

    if (!is_null($date_request)) {
      $contReq = $contReq
        ->whereDate('updated_at', '<=', Carbon::parse('1 ' . $date_request['month'] . ' ' . $date_request['year']));
    }

    $contReq = $contReq->first();

    if (is_null($contReq)) {
      return $fromPagIbigTable;
    } else {
      return $contReq->amount;
    }
  }
  public function GSIS($total_salary, $PS)
  {
    $gsis = \DB::table('gsis')->first();

    if (!$gsis) {
      return 0;
    }

    if ($PS) {
      return round($gsis->personal_share * $total_salary, 2);
    } else {
      return round($gsis->government_share * $total_salary, 2);
    }
  }

  public function pag_ibig($total_salary, $PS, $date = null, $id)
  {
    // TODO: add in parameters employee_id
    //add index
    $pag_ibig = \DB::table('pagibig')
      ->where('minimum_range', '<=', $total_salary)
      ->where('maximum_range', '>=', $total_salary)
      ->first();

    if (!$pag_ibig) {
      return 0;
    }

    // TO DO need to check if theres existing data on table contribution table if status is 1 use the data/
    if ($PS) {
      //$pagibig_contribution = $query->personal_share; chnaged July 24,2020
      $result = $this->query_pagibig_contribution_requests('pagibig', $id, $pag_ibig->personal_share, $date);

      if ($date) {
        // Query for historical contributions based from payroll.
        $result =  (float) \App\PayrollEmployeeLog::where([
          ['employee_id', $id],
          ['type_of_string', 'pagibig'],
          ['year', $date['year']]
        ])
          ->sum('amount') ?? $result;
      }
    } else {
      $result = $pag_ibig->government_share;
    }

    return $result;
  }

  public function phil($total_salary, $PS, $PR)
  {
    $query = \DB::table('philhealth')
      ->where('minimum_range', '<=', $total_salary)
      ->where('maximum_range', '>=', $total_salary)
      ->first();

    if (!$query) {
      return 0;
    }

    if ($query->is_max) {
      if ($PS) {
        return json_decode($query->personal_share[0]);
      } else {
        return json_decode($query->government_share[0]);
      }
    } else {
      if ($PS) {
        return round(($total_salary * ($query->percentage / 100)) / 2, 2, PHP_ROUND_HALF_DOWN);
      } else {
        return round(($total_salary * ($query->percentage / 100)) / 2, 2, PHP_ROUND_HALF_UP);
      }
    }
  }

  public function ECC()
  {
    $query = \DB::table('gsis')->first();
    $ECC = $query ? $query->ecc : 0;

    return $ECC;
  }

  public function Tax()
  {
    $query = \DB::table('gsis')->first();
    $ECC = $query ? $query->ecc : 0;

    return $ECC;
  }

  public function query_contributions($salary, $date, $id)
  {
    $data = [
      "GSIS-PS" => $this->GSIS($salary, true),
      "GSIS-GS" => $this->GSIS($salary, false),
      "PAGIBIG-PS" => $this->pag_ibig($salary, true, $date, $id),
      "PAGIBIG-GS" => $this->pag_ibig($salary, false, null, $id),
      "PH-PS" => $this->phil($salary, true, false),
      "PH-GS" =>  $this->phil($salary, false, false),
      "PH-MONTHLY-PREMIUM" => $this->phil($salary, false, true),
      "ECC" => $this->ECC()
    ];
    return array("month" => $date['month'], "data" => $data);
  }

  public function remittance($salary, $date, $id, $type)
  {
    if ($type === 'pagibig') {
      $ps = $this->pag_ibig($salary, true, $date, $id);
      $gov = $this->pag_ibig($salary, false, null, $id);
    } else if ($type === 'gsis') {
      $ps = $this->GSIS($salary, true);
      $gov = $this->GSIS($salary, false);
    } else if ($type === 'phil') {
      $ps = $this->phil($salary, true, false);
      $gov = $this->phil($salary, false, false);
    } else {
      $ps = 0;
      $gov = 0;
    }
    $ecc = $this->ECC();
    $total = $ps + $gov + $ecc;
    $data = [
      "PS-SHARE" => $ps,
      "GOV-SHARE" => $gov,
      "ECC" => $ecc,
      "TOTAL" => $total
    ];

    return array("month" => $date['month'], "data" => $data);
  }

  use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

  public function approvers()
  {
    return $this->hasManyDeep(
      '\App\ApprovalItemEmployee',
      ['\App\ApprovalRequest', '\App\ApprovalLevel', '\App\ApprovalItem'],
      [
        'id',
        'approval_request_id',
        'approval_level_id',
        'approval_item_id',
      ],
      [
        'approval_request_id',
        'id',
        'id',
        'id',
      ]
    );
  }

  public function exportRemittances($results, $dateTitle = "", $additionalColumns = [])
  {
    $streamedResponse = new StreamedResponse();
    $streamedResponse->setCallback(function () use ($results, $dateTitle, $additionalColumns) {

      $inputFileType = 'Xlsx';
      $inputFileName = './forms/Remittances.xlsx';
      // Redirect output to a client’s web browser (Xlsx)
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Cache-Control: max-age=0');
      // If you're serving to IE 9, then the following may be needed
      header('Cache-Control: max-age=1');

      // If you're serving to IE over SSL, then the following may be needed
      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
      header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
      header('Pragma: public'); // HTTP/1.0

      $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

      $reader->setLoadSheetsOnly('Sheet1');

      $spreadsheet = $reader->load($inputFileName);
      $worksheet = $spreadsheet->getActiveSheet();
      $worksheet->getCell('A3')->setValue($dateTitle);

      $loop = 5; //column row
      $col = 18; //start of dynamic columns
      $columsObj = [];
      foreach ($additionalColumns as $columns) {
        $array = array();
        $worksheet->setCellValueByColumnAndRow($col, $loop, $columns);
        $array['col'] = $col;
        $array['column_name'] = $columns;
        array_push($columsObj, $array);
        $col++;
      }

      $loop = 6;
      foreach ($results as $result) {
        $worksheet->getCell('A' . $loop)->setValue($result["id"]);
        $worksheet->getCell('B' . $loop)->setValue($result["gsis_number"] !== '0' ? $result["gsis_number"] : '');
        $worksheet->getCell('C' . $loop)->setValue($result["pagibig_number"] !== '0' ? $result["pagibig_number"] : '');
        $worksheet->getCell('D' . $loop)->setValue($result["philhealth_number"] !== '0' ? $result["philhealth_number"] : '');
        $worksheet->getCell('E' . $loop)->setValue($result["tin"] !== 0 ? $result["tin"] : '');
        $worksheet->getCell('F' . $loop)->setValue($result["last_name"]);
        $worksheet->getCell('G' . $loop)->setValue($result["first_name"]);
        $worksheet->getCell('H' . $loop)->setValue($result["middle_name"]);
        $worksheet->getCell('I' . $loop)->setValue($result["name_extension"] !== 'NA' ? $result["name_extension"] : '');
        $worksheet->getCell('J' . $loop)->setValue($result["gsis_ps"] ?? '');
        $worksheet->getCell('K' . $loop)->setValue($result["gsis_gs"] ?? '');
        $worksheet->getCell('L' . $loop)->setValue($result["gsis_ecc"] ?? '');
        $worksheet->getCell('M' . $loop)->setValue($result["pagibig_ps"] ?? '');
        $worksheet->getCell('N' . $loop)->setValue($result["pagibig_gs"] ?? '');
        $worksheet->getCell('O' . $loop)->setValue($result["philhealth_ps"] ?? '');
        $worksheet->getCell('P' . $loop)->setValue($result["philhealth_gs"] ?? '');
        $worksheet->getCell('Q' . $loop)->setValue($result["tax"] ?? '');

        if (isset($result['loan_deductions'])) {
          foreach ($result['loan_deductions'] as $item => $value) {
            $key = array_search(strtoupper($item), array_column($columsObj, 'column_name'));
            if ($value != 0) $worksheet->setCellValueByColumnAndRow($columsObj[$key]['col'], $loop, $value);
          }
        }
        $loop++;
      }
      $rowEndLoop = $loop;
      $loop++;
      $worksheet->getCell('B' . $loop)->setValue('Total')->getStyle('B' . $loop)->getFont()->setBold(true);
      $worksheet->getStyle('J' . $loop . ':' . 'AZ' . $loop)->getFont()->setBold(true);
      $worksheet->getStyle('J' . $loop . ':' . 'AZ' . $loop)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
      $worksheet->getStyle('J' . $loop . ':' . 'AZ' . $loop)->getNumberFormat()->setFormatCode('#,##0.00');

      $worksheet->setCellValue('J' . $loop, '=SUM(J6:J' . $loop . ')');
      $worksheet->setCellValue('K' . $loop, '=SUM(K6:K' . $loop . ')');
      $worksheet->setCellValue('L' . $loop, '=SUM(L6:L' . $loop . ')');
      $worksheet->setCellValue('M' . $loop, '=SUM(M6:M' . $loop . ')');
      $worksheet->setCellValue('N' . $loop, '=SUM(N6:N' . $loop . ')');
      $worksheet->setCellValue('O' . $loop, '=SUM(O6:O' . $loop . ')');
      $worksheet->setCellValue('P' . $loop, '=SUM(P6:P' . $loop . ')');
      $worksheet->setCellValue('Q' . $loop, '=SUM(Q6:Q' . $loop . ')');

      foreach ($columsObj as $column) {
        $columnAlpha = Contribution::getColFromNumber($column['col']);
        $worksheet->setCellValue($columnAlpha . $loop, '=SUM(' . $columnAlpha . '6:' . $columnAlpha . $loop . ')');
      }

      $loop++;
      $totalRow = $loop;
      $worksheet->getCell('B' . $loop)->setValue('TOTAL GSIS PS');
      $worksheet->setCellValue('D' . $loop, '=SUM(J6:J' . $rowEndLoop . ')');
      $loop++;
      $worksheet->getCell('B' . $loop)->setValue('TOTAL GSIS GS');
      $worksheet->setCellValue('D' . $loop, '=SUM(K6:K' . $rowEndLoop . ')');
      $loop++;
      $worksheet->getCell('B' . $loop)->setValue('TOTAL GSIS ECC');
      $worksheet->setCellValue('D' . $loop, '=SUM(L6:L' . $rowEndLoop . ')');
      $loop++;
      $worksheet->getCell('B' . $loop)->setValue('TOTAL PAGIBIG PS');
      $worksheet->setCellValue('D' . $loop, '=SUM(M6:M' . $rowEndLoop . ')');
      $loop++;
      $worksheet->getCell('B' . $loop)->setValue('TOTAL PAGIBIG GS');
      $worksheet->setCellValue('D' . $loop, '=SUM(N6:N' . $rowEndLoop . ')');
      $loop++;
      $worksheet->getCell('B' . $loop)->setValue('TOTAL PHILHEALTH PS');
      $worksheet->setCellValue('D' . $loop, '=SUM(O6:O' . $rowEndLoop . ')');
      $loop++;
      $worksheet->getCell('B' . $loop)->setValue('TOTAL PHILHEALTH GS');
      $worksheet->setCellValue('D' . $loop, '=SUM(P6:P' . $rowEndLoop . ')');
      $loop++;
      $worksheet->getCell('B' . $loop)->setValue('TOTAL TAX');
      $worksheet->setCellValue('D' . $loop, '=SUM(Q6:Q' . $rowEndLoop . ')');
      $loop++;

      foreach ($columsObj as $column) {
        $columnAlpha = Contribution::getColFromNumber($column['col']);
        $worksheet->getCell('B' . $loop)->setValue('TOTAL ' . strtoupper($column['column_name']));
        $worksheet->setCellValue('D' . $loop, '=SUM(' . $columnAlpha . '6:' . $columnAlpha . $rowEndLoop . ')');
        $loop++;
      }

      $worksheet->getCell('B' . $loop)->setValue('TOTAL REMITTANCES');
      $worksheet->getStyle('B' . $loop . ':' . 'D' . $loop)->getFont()->setBold(true);
      $worksheet->getStyle('B' . $loop . ':' . 'D' . $loop)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
      $worksheet->setCellValue('D' . $loop, '=SUM(D' . $totalRow . ':D' . $loop . ')');
      $worksheet->getStyle('D' . $totalRow . ':' . 'D' . $loop)->getNumberFormat()->setFormatCode('#,##0.00');

      Contribution::reportSignature($worksheet, $loop + 3);

      $writer =  new Xlsx($spreadsheet);
      $writer->save('php://output');
      die;
    });

    $streamedResponse->setStatusCode(Response::HTTP_OK);
    $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . 'Remittances' . "_" . $dateTitle . '.xlsx"');

    return $streamedResponse;
  }

  public static function getColFromNumber($num)
  {
    $numeric = ($num - 1) % 26;
    $letter = chr(65 + $numeric);
    $num2 = intval(($num - 1) / 26);
    if ($num2 > 0) {
      return Contribution::getColFromNumber($num2) . $letter;
    } else {
      return $letter;
    }
  }

  public static function reportSignature($worksheet, $start)
  {
    $data = \App\Signatories::where('report_name', 'Remittance Report (Masterlist)')->first();
    $signatory = $data->signatories;

    $worksheet->getCell('B' . $start)->setValue('created by:');
    $worksheet->getCell('J' . $start)->setValue('Certified by:');
    $start += 3;
    $worksheet->getStyle('B' . $start . ':' . 'J' . $start)->getFont()->setBold(true);
    $worksheet->getStyle('B' . $start . ':' . 'C' . $start)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    $worksheet->mergeCells('B' . $start . ':' . 'C' . $start);
    $worksheet->getCell('B' . $start)->setValue(
      ($signatory[0]['name'] ?? '')
    );
    $worksheet->getStyle('B' . $start)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $worksheet->mergeCells('J' . $start . ':' . 'K' . $start);
    $worksheet->getCell('J' . $start)->setValue(
      ($signatory[0]['name'] ?? '')
    );
    $worksheet->getStyle('J' . $start . ':' . 'K' . $start)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    $worksheet->getStyle('J' . $start)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $start++;
    $worksheet->mergeCells('J' . $start . ':' . 'K' . $start);
    $worksheet->getCell('J' . $start)->setValue(
      ($signatory[0]['title'] ?? '')
    );
    $worksheet->getStyle('J' . $start)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
  }
}
