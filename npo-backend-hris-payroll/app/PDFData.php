<?php

namespace App;

class PDFData
{
    protected $x;
    protected $y;
    protected $cellWidth;
    protected $cellHeight;

    protected $text;
    protected $font;
    protected $fontSize;
    protected $fontStyle;

    public function __construct(int $x, int $y, $cellWidth, $cellHeight, $text = '', $fontSize = null, $font = 'Courier', $fontStyle = '')
    {
        $this->x = $x;
        $this->y = $y;
        $this->text = $text;
        $this->font = $font;
        $this->fontStyle = $fontStyle;
        $this->cellWidth = $cellWidth;
        $this->cellHeight = $cellHeight;
        if ($fontSize) {
            $this->fontSize = $fontSize;
        } else {
            $this->fontSize = $this->computeFontSize();
        }
    }

    private function computeFontSize()
    {
        $multiplier = 4;
        return max(7, min(round($this->cellWidth / max(strlen($this->text), 1) * $multiplier, 2), 10));
    }

    public function getcellWidth()
    {
        return $this->cellWidth;
    }

    public function getcellHeight()
    {
        return $this->cellHeight;
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getFont()
    {
        return $this->font;
    }

    public function getFontSize()
    {
        return $this->fontSize;
    }

    public function getFontStyle()
    {
        return $this->fontStyle;
    }
}
