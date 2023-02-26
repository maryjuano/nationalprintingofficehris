<?php

namespace App\Reports;

interface Certificate
{
    public function getCategory() : string;

    public function getType() : string;

    public function getTotalAmount() : float;

    public function getEmployeeName() : string;

    public function getDivision() : string;

    public function getDateGenerated() : \DateTime;

    public function getPeriodStart() : \DateTime;

    public function getPeriodEnd() : \DateTime;

    public function getDetails() : \Illuminate\Support\Collection;
    
    public function getDetailCount() : int;

    public function getSignatories() : \Illuminate\Support\Collection;
}
