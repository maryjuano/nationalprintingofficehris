<?php

namespace App\Reports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

abstract class CertificateDetail
{
    protected $parent;
    protected $index;

    public function __construct(Certificate $parent, int $index)
    {
        $this->parent = $parent;
        $this->index = $index;
    }

    abstract public function getPeriodYear() : int;

    abstract public function getPeriodMonth() : int;

    abstract public function getORNumber() : ?string;

    abstract public function getORDate() : ?\DateTime;

    abstract public function getAmount() : ?float;

    abstract public function getRemarks() : ?string;

    public function displayYear() : bool
    {
        return $this->getPeriodMonth() == 1 || $this->index === 0;
    }

    public function detailsWithSameYear() : \Illuminate\Support\Collection
    {
        return $this->parent->getDetails()->filter(function ($detail) {
            return $detail->getPeriodYear() == $this->getPeriodYear();
        });
    }

    public function getYearEndTotal() : ?float
    {
        if ($this->getPeriodMonth() != 12 || $this->index == $this->parent->getDetailCount() - 1) {
            return null;
        }

        return $this->detailsWithSameYear()->sum(function ($detail) {
            return $detail->getAmount();
        });
    }
}
