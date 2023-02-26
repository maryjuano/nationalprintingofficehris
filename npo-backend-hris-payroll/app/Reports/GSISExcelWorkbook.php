<?php

namespace App\Reports;

use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GSISExcelWorkbook implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    public function __construct(string $type, array $certificates)
    {
        $this->sheets = $this->generateSheets($type, $certificates);
    }

    public function sheets(): array
    {
        return $this->sheets->all();
    }

    private function generateSheets(string $type, array $certificates)
    {
        return collect($certificates)->groupBy(function ($certificate) {
            return $certificate->getDivision();
        })->map(function ($certificates, $division) use ($type) {
            return new GSISExcelSheet($type, $division, $certificates->all());
        });
    }
}
