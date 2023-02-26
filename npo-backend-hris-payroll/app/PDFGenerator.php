<?php

namespace App;

use setasign\Fpdi\Fpdi;

class PDFGenerator
{
    protected $pageDataTemplates = [];
    protected $pdf;
    protected $sourceFile;
    protected $pdfData;

    public function __construct($sourceFile, $pdfData)
    {
        $this->pdf = new Fpdi();
        $this->sourceFile = $sourceFile;
        $this->pdfData = $pdfData;
        $this->generate();
    }

    protected function generate()
    {
        $this->pdf->setSourceFile($this->sourceFile);
        $pageCount = $this->pdfData->getTotalPages();
        for ($pageNo = 0; $pageNo < $pageCount; $pageNo++) {
            $importedPage = $this->pdf->importPage($this->pdfData->getPageMap()[$pageNo] + 1);
            $this->pdf->AddPage();
            $this->pdf->useTemplate($importedPage, ['adjustPageSize' => true]);
            if (isset($this->pdfData->getPageData()[$pageNo])) {
                $page = $this->pdfData->getPageData()[$pageNo];

                foreach ($page as $i => $data) {
                    $this->pdf->setFont($data->getFont(), $data->getFontStyle(), $data->getFontSize());
                    
                    $this->pdf->SetXY($data->getX(), $data->getY());
                    $cellHeight = $this->pdf->GetStringWidth($data->getText()) >= $data->getCellWidth() ? 
                        $data->getCellHeight() / 2 : $data->getCellHeight();
                    
                    $this->pdf->MultiCell(
                        $data->getCellWidth(),
                        $cellHeight,
                        $data->getText(),
                        0,
                        'L',
                        // true // Shade cell for testing
                    );

                    // $this->pdf->Text($data->getX(), $data->getY(), $data->getText());
                }
            }
        }
    }

    public function output()
    {
        return $this->pdf->Output();
    }
}
