<?php

namespace App\Reports;

use App\Employee;
use App\Attendance;
use Carbon\Carbon;

class LeaveCard
{
	protected $employeeId;
	protected $year;

	protected $employee;
	protected $months = [];

	public function __construct(int $employeeId, int $year)
	{
		$this->employeeId = $employeeId;
		$this->year = $year;
		$this->employee = Employee::with([
			'time_off_balances',
			'employment_and_compensation',
			'personal_information',
			'dtrs' => function ($q) use ($year) {
				return $q->approved()->whereYear('dtr_date', $year);
			},
		])->find($employeeId);

		$monthlyDTRs = $this->employee->dtrs->groupBy(function ($dtr) {
			return Carbon::parse($dtr->dtr_date)->month;
		});

		$date = Carbon::create($year, 1, 1);
		$startDate = $date->copy()->startOfYear();
		$endDate   = $date->copy()->endOfYear();
		$monthlyHolidays = Attendance::getHolidays($startDate, $endDate)->groupBy(function ($holiday) {
			return Carbon::parse($holiday->holiday_date)->month;
		});

		for ($i = 0; $i < 12; $i++) {
			$this->months[] = new LeaveCardMonth($this->employee, $year, $i + 1, $monthlyDTRs[$i + 1] ?? collect(), $monthlyHolidays[$i + 1] ?? collect());
		}
	}

	public function getFullName()
	{
		return sprintf(
			'%s %s %s',
			$this->employee->personal_information->first_name,
			$this->employee->personal_information->middle_name,
			$this->employee->personal_information->last_name
		);
	}

	public function getDivision()
	{
		return $this->employee->department;
	}

	public function getCalendarYear()
	{
		return $this->year;
	}

	public function getMonths()
	{
		return $this->months;
	}
}
