<?php

namespace App\Reports;

use App\Dtr;
use App\Holiday;
use App\Employee;
use Carbon\Carbon;
use App\WorkSchedule;
use App\Helpers\WorkscheduleTimeOption;

class LeaveCardDay
{
	protected $dtr;
	protected $holiday;
	protected $employee;
	protected $workschedule;

	protected $check = false;
	protected $data = '';
	protected $date;

	public function __construct(Employee $employee, Carbon $date, ?Dtr $dtr, ?Holiday $holiday)
	{
		$this->dtr = $dtr;
		$this->holiday = $holiday;
		$this->employee = $employee;
		$this->date = $date;

		// Note: This assumes a singular and permanent work_schedule per employee
		$this->workschedule = $employee->employment_and_compensation->work_schedule;

		$this->data = $this->initData();
	}

	public function getDtr()
	{
		return $this->dtr;
	}

	public function getDate()
	{
		return $this->date;
	}

	public function isRestday()
	{
		return $this->dtr->is_restday ?? false;
	}

	public function isHoliday()
	{
		return !!$this->holiday ?? false;
	}

	public function isWeekend()
	{
		if ($this->dtr == null) {
			return false;
		}
		return Carbon::parse($this->dtr->dtr_date)->isWeekend() ?? false;
	}

	public function isRestdayOrWeekend()
	{
		return $this->isRestday() || $this->isWeekend();
	}

	public function isCheck()
	{
		return $this->check;
	}

	protected function completeWorkingHours(Dtr $dtr, WorkSchedule $workschedule)
	{
		if (
			$workschedule->time_option == WorkscheduleTimeOption::REGULAR_7AM_TO_4PM
			|| $workschedule->time_option == WorkscheduleTimeOption::REGULAR_8AM_TO_5PM
			|| $workschedule->time_option == WorkscheduleTimeOption::EIGHT_HRS_PER_DAY
		) {
			return $dtr->rendered_minutes >= 480;
		}

		// If WorkSchedule is Flexible Weekly Hours or Non Time Based
		// Add check mark if they clocked in and out.
		if (
			$workschedule->time_option == WorkscheduleTimeOption::FORTY_HRS_PER_WEEK
			|| $workschedule->time_option == WorkscheduleTimeOption::NON_TIME_BASED
		) {
			return $dtr->in->schedule && isset($dtr->in->from_source) && $dtr->in->from_source
				&& $dtr->out->schedule && isset($dtr->out->from_source) && $dtr->out->from_source;
		}

		return false;
	}

	protected function htmlFraction($hours, $mins)
	{
		return
			"<table style='border: none;'>
		<tr style='border: none;'>
			<th style='border: none;' style='width: 50%;'></th>
			<th style='border: none;' style='width: 50%;'></th>
		</tr>
		<tr style='border: none;'>
			<td style='border: none;'>
				<div style='display:inline-block; width : 50%; font-size : 12px;'>
				{$hours}
				</div>
			</td>
			<td style='border: none;'>
				<div style='width : 50%; font-size : 12px;'>
					<div style='border-bottom : 1px solid #000; '>
					{$mins}
					</div>
					<div>
					60
					</div>
				</div>
			</td>
		</tr>
	</table>";
	}

	protected function initData()
	{
		if ($this->holiday != null) {
			return "H";
		}
		
		if ($this->dtr === null) {
			return null;
		}

		if ($this->dtr->time_off_request !== null && $this->dtr->time_off_request->status == 1) {
			return $this->dtr->time_off_request->code;
		}

		if ($this->dtr->absence === 1) {
			return "A";
		}

		if ($this->dtr->undertime_minutes > 0 || $this->dtr->late_minutes > 0) {
			$totalMinutes = $this->dtr->undertime_minutes + $this->dtr->late_minutes;
			$hours = intdiv($totalMinutes, 60);
			$minutes = $totalMinutes % 60;
			return $this->htmlFraction($hours > 0 ? $hours : '', $minutes);
		}

		// Mark check if complete working hours
		if ($this->completeWorkingHours($this->dtr, $this->workschedule)) {
			$this->check = true;
			return '';
		}
	}

	public function getData()
	{
		return $this->data;
	}
}
