<?php

namespace App\Reports;

use App\Employee;
use Carbon\Carbon;
use App\Attendance;
use App\Helpers\DayFractions;
use App\Helpers\Months;
use Carbon\CarbonInterval;

class LeaveCardMonth
{
    protected $month;
    protected $dtrs;
    protected $absenceForVLDeduction = null;
    protected $lateForVLDeduction = null;
    protected $vlDaysBalance = null;
    protected $absenceForSLDeduction = null;
    protected $approvedSL = null;
    protected $leavesWithoutPay = null;

    protected $days = [];
    const DECIMAL_POINTS = 3;

    public function __construct(Employee $employee, int $year, int $month, $dtrs, $holidays)
    {
        $this->month = $month;
        $this->dtrs = $dtrs;

        for ($day = 1; $day <= 31; $day++) {
            $dtr = $dtrs->first(function ($dtr) use ($year, $month, $day) {
                return Carbon::parse($dtr->dtr_date)->startOfDay()->eq(Carbon::createFromDate($year, $month, $day)->startOfDay());
            });
            $holiday = $holidays->first(function ($holiday) use ($year, $month, $day) {
                if ($holiday->is_recurring) {
                    return Carbon::parse($holiday->holiday_date)->year($year)->startOfDay()->eq(Carbon::createFromDate($year, $month, $day)->startOfDay());
                } else {
                    return Carbon::parse($holiday->holiday_date)->startOfDay()->eq(Carbon::createFromDate($year, $month, $day)->startOfDay());
                }
            });
            $this->days[] = new LeaveCardDay($employee, Carbon::create($year, $month, $day)->startOfDay(), $dtr, $holiday);
        }

        if (!$dtrs->isEmpty()) {
            $lastDayOfMonth = Carbon::createFromDate($year, $month)->lastOfMonth();
            $this->absenceForVLDeduction = $dtrs->sum('absence_for_vl_deduction');
            $this->lateForVLDeduction = $dtrs->sum('late_for_vl_deduction');
            $this->vlDaysBalance = $employee->time_off_balances()->vl()->exists()
                ? $employee->time_off_balances()->vl()->first()->getBalance($lastDayOfMonth)
                : 0;
            $this->absenceForSLDeduction = $dtrs->sum('absence_for_sl_deduction');
            $this->slDaysBalance = $employee->time_off_balances()->sl()->exists()
                ? $employee->time_off_balances()->sl()->first()->getBalance($lastDayOfMonth)
                : 0;

            $this->approvedSL = $employee->time_off_requests()->sl()->approved()
                ->monthUpdated($month)
                ->yearUpdated($year)
                ->count();
        }

        $this->leavesWithoutPay = $employee->time_off_requests()->vlSl()->approved()->withoutPay()
            ->monthUpdated($month)
            ->yearUpdated($year)
            ->sum('total_days');
    }

    public function getLeaveDates()
    {
        return $this->dtrs->filter(function ($dtr) {
            return $dtr !== null && $dtr->time_off_request !== null && $dtr->time_off_request->status == 1;
        })->map(function ($dtr) {
            return $dtr->time_off_request->code . ': ' . date_format(date_create(Carbon::parse($dtr->dtr_date)), 'm/d');
        });
    }

    public function getDay(int $day)
    {
        return $this->days[$day - 1];
    }

    public function getDays()
    {
        return $this->days;
    }

    public function getVLDaysTaken()
    {
        if ($this->dtrs->isEmpty()) {
            return null;
        }
        $cascades = CarbonInterval::getCascadeFactors(); // save initial factors
        CarbonInterval::setCascadeFactors([
            'minute' => [60, 'seconds'],
            'hour' => [60, 'minutes'],
            'day' => [8, 'hours'],
        ]);
        $interval = Attendance::convertLeavePointsToTime(
            bcadd($this->lateForVLDeduction, $this->absenceForVLDeduction, DayFractions::SCALE)
        )->cascade();
        CarbonInterval::setCascadeFactors($cascades); // restore original factors
        return trim(sprintf(
            "%s.%s.%s",
            $interval->dayz,
            $interval->hours,
            $interval->minutes
        ));
    }

    public function getVLPointsTaken()
    {
        if ($this->dtrs->isEmpty()) {
            return null;
        }
        return number_format(
            bcadd($this->absenceForVLDeduction, $this->lateForVLDeduction, DayFractions::SCALE),
            self::DECIMAL_POINTS
        );
        // return bcdiv(
        //     bcadd($this->absenceForVLDeduction, $this->lateForVLDeduction, DayFractions::SCALE),
        //     1,
        //     self::DECIMAL_POINTS
        // );
    }

    public function getVLDaysBalance()
    {
        if ($this->dtrs->isEmpty()) {
            return null;
        }

        return number_format($this->vlDaysBalance, self::DECIMAL_POINTS);
        // return bcdiv($this->vlDaysBalance, 1, self::DECIMAL_POINTS);
    }

    public function getSLDaysTaken()
    {
        if ($this->dtrs->isEmpty()) {
            return null;
        }
        return $this->absenceForSLDeduction;
    }

    public function getApprovedSLText()
    {
        if (!$this->approvedSL) {
            return null;
        }
        return sprintf('(-%s SL)', $this->approvedSL);
    }

    public function getSLDaysBalance()
    {
        if ($this->dtrs->isEmpty()) {
            return null;
        }
        return number_format($this->slDaysBalance, self::DECIMAL_POINTS);
        // return bcdiv($this->slDaysBalance, 1, self::DECIMAL_POINTS);
    }

    public function getLeavesWithoutPay()
    {
        if (!$this->leavesWithoutPay) {
            return null;
        }
        return number_format($this->leavesWithoutPay, self::DECIMAL_POINTS);
    }

    public function getMonth()
    {
        return Months::FULL_NAMES[$this->month - 1];
    }

    // public function getTotalSLAndVL()
    // {
    //     if ($this->dtrs->isEmpty()) {
    //         return null;
    //     }

    //     return bcdiv(bcadd(
    //         ($this->absenceForSLDeduction + $this->absenceForVLDeduction),
    //         $this->lateForVLDeduction,
    //         DayFractions::SCALE
    //     ), 1, self::DECIMAL_POINTS);
    // }

    public function getTotalSLAndVLBalance()
    {
        if ($this->dtrs->isEmpty()) {
            return null;
        }

        return bcadd($this->getSLDaysBalance(), $this->getVLDaysBalance(), self::DECIMAL_POINTS);
    }
}
