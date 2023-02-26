<?php

namespace App\Reports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class GSISCertificateDetail extends CertificateDetail
{
    protected $orDate;
    protected $orDetail;
    protected $periodYear;
    protected $periodMonth;

    public function __construct(
        $orDetail,
        int $index,
        GSISCertificate $parent
    ) {
        parent::__construct($parent, $index);
        $this->orDetail = $orDetail;
        $this->orDate = $orDetail['or_date'] !== null ? Carbon::parse($orDetail['or_date']) : null;
        $this->periodYear = $orDetail['period_year'];
        $this->periodMonth = $orDetail['period_month'];
    }

    public function getPeriodMonth() : int
    {
        return $this->periodMonth;
    }

    public function getPeriodYear() : int
    {
        return $this->periodYear;
    }

    public function getORNumber() : ?string
    {
        return $this->orDetail['or_number'];
    }

    public function getORDate() : ?\DateTime
    {
        return $this->orDate;
    }

    public function getAmount() : ?float
    {
        return $this->orDetail['amount'];
    }

    public function getRemarks() : ?string
    {
        return $this->orDetail['remarks'];
    }
}
