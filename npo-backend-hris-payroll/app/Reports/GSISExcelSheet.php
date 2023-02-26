<?php

namespace App\Reports;

use Carbon\Carbon;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class GSISExcelSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    protected $type;
    protected $division;
    protected $certificates;

    public function __construct(string $type, string $division, array $certificates)
    {
        $this->type = $type;
        $this->division = $division;
        $this->certificates = collect($certificates);
    }

    public function view(): View
    {
        return view('excel.gsis_excel', ['sheet' => $this]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->addHeaders($event->sheet);
                $this->addFooters($event->sheet);
            },
        ];
    }

    private function addHeaders($sheet)
    {
        $sheet->insertNewRowBefore(1, 3);
        $sheet->setCellValue('A1', 'Certificate:');
        $sheet->setCellValue('B1', $this->type);
        $sheet->setCellValue('A2', 'Division:');
        $sheet->setCellValue('B2', $this->division);
        // TODO range
        $sheet->getStyle('B1:B2')->getFont()->setBold(true);
    }

    private function addFooters($sheet)
    {
        $tallyStartRow = $this->getCertificateDetails()->count() + 9;
        foreach ($this->getTotalAmountsByYear() as $year => $amount) {
            $sheet->setCellValue("B{$tallyStartRow}", "TOTAL {$year}");
            $sheet->setCellValue("C{$tallyStartRow}", $amount);
            $sheet->getStyle("C{$tallyStartRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $tallyStartRow += 1;
        }

        $sheet->setCellValue("B{$tallyStartRow}", "TOTAL {$this->type}");
        $sheet->getStyle("B{$tallyStartRow}")->getFont()->setBold(true);
        $sheet->getStyle("B{$tallyStartRow}")->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

        $sheet->setCellValue("C{$tallyStartRow}", $this->getTotalAmountsByYear()->sum());
        $sheet->getStyle("C{$tallyStartRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("C{$tallyStartRow}")->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'EBEDEF'
                    ]
                ]
            ],
            'G' => ['numberFormat' => ['formatCode' => '#,##0.00']],
            'F' => ['numberFormat' => ['formatCode' => 'mmmm d, yyyy']],
            'A2:B2' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                ]
            ]
        ];
    }

    public function title(): string
    {
        return $this->division;
    }
    
    public function getCertificates()
    {
        return $this->certificates;
    }

    private function getCertificateDetails()
    {
        return $this->certificates->flatMap(function ($certificate) {
            return $certificate->getDetails();
        });
    }

    private function getTotalAmountsByYear()
    {
        return $this->getCertificateDetails()->groupBy(function ($detail) {
            return $detail->getPeriodYear();
        })->map(function ($details) {
            return $details->sum(function ($detail) {
                return $detail->getAmount();
            });
        });
    }
}
