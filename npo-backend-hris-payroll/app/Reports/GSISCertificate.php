<?php

namespace App\Reports;

use Carbon\Carbon;

class GSISCertificate implements Certificate
{
    const CATEGORY = 'GSIS';

    protected $details;
    protected $dateGenerated;
    protected $type;
    protected $employe;
    protected $periodStart;
    protected $periodEnd;
    protected $division;

    public function __construct(
        string $type,
        $employeeName,
        $periodStart,
        $periodEnd,
        $orDetails,
        $division = null
    ) {
        $this->type = $type;
        $this->employeeName = $employeeName;
        $this->periodStart = Carbon::parse($periodStart);
        $this->periodEnd = Carbon::parse($periodEnd);
        $this->details = $this->generateDetails($orDetails);
        $this->dateGenerated = now();
        $this->division = $division;
    }

    protected function generateDetails($orDetails)
    {
        return collect($orDetails)->map(function ($orDetail, $key) {
            return new GSISCertificateDetail(
                $orDetail,
                $key,
                $this
            );
        })->sortBy(function ($detail) {
            return $detail->getPeriodYear() * 100 + $detail->getPeriodMonth();
        })->values();
    }

    public function getDetailCount() : int
    {
        return $this->details->count();
    }

    public function getEmployeeName() : string
    {
        return $this->employeeName;
    }

    public function getDivision() : string
    {
        return $this->division;
    }

    public function getDateGenerated() : \DateTime
    {
        return $this->dateGenerated;
    }

    public function getPeriodStart() : \DateTime
    {
        return $this->periodStart;
    }

    public function getPeriodEnd() : \DateTime
    {
        return $this->periodEnd;
    }

    public function getDetails() : \Illuminate\Support\Collection
    {
        return $this->details;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getCategory() : string
    {
        return self::CATEGORY;
    }

    public function getTotalAmount() : float
    {
        return $this->details->sum(function ($detail) {
            return $detail->getAmount();
        });
    }

    public function getSignatories() : \Illuminate\Support\Collection
    {
        return collect([]);
    }
}
