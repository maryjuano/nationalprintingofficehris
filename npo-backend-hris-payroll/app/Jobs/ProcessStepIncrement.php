<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Log;
use Carbon\Carbon;

class ProcessStepIncrement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Starting ProcessStepIncrement Job...");
        // Get salary grade / steps / latest last
        $salaries = \App\Salary::orderBy('id')->get();;
        $lookup = array();
        foreach ($salaries as $salary) {
            $lookup[$salary->grade] = $salary->step;
        }
        Log::debug($lookup);

        foreach (\App\Employee::all() as $employee) {
            Log::info("Processing {$employee->name}");

            // Check if employment_and_compensation is setup for this employee
            if (!isset($employee->employment_and_compensation->salary_grade_id)) {
                Log::warning("- Skipping: Employment and Compensation grade data not set.");
                continue;
            }

            $grade = $employee->employment_and_compensation->salary_grade_id;
            $step = $employee->employment_and_compensation->step_increment;

            Log::debug(
                "- Grade: {$grade}"
                    . "; " . "Step: {$step}"
                    . "; " . "Job Info Effectivity Date: {$employee->employment_and_compensation->job_info_effectivity_date}"
            );
            // Check current grade and step
            if (sizeof($lookup[$grade]) - 1 == $step) {
                Log::info("- Already at maximum step of current grade {$grade}");
                continue;
            }
            // Check job_info_effectivity_date
            $three_years_ago = Carbon::now()->subYears(3)->addMonths(1);
            $job_info_effectivity_date = Carbon::parse($employee->employment_and_compensation->job_info_effectivity_date);
            if ($job_info_effectivity_date->lt($three_years_ago)) {
                Log::info("- Will increase step of employee");
                // create notification
                $employee->employee_id = $employee->id;
                \App\Notification::create_hr_notification(
                    ['nosi'],
                    $employee->name . ' is eligible for Step Increment on ' . $job_info_effectivity_date->addYears(3)->format('m/d/Y'),
                    \App\Notification::NOTIFICATION_SOURCE_NOSI,
                    $employee->id,
                    $employee
                );


                // $emp_and_comp = \App\EmploymentAndCompensation::where('employee_id', $employee->id)->first();
                // $emp_and_comp->step_increment = $step + 1;
                // $emp_and_comp->job_info_effectivity_date = Carbon::now();
                // $emp_and_comp->save();

                // create entry in notice of step increment
                // $nosi = new \App\NoticeOfStepIncrement();
                // $nosi->employee_id = $employee->id;
                // $nosi->generated_date = Carbon::now();
                // $nosi->effectivity_date = Carbon::now();
                // $nosi->old_rate = $lookup[$grade][$step];
                // $nosi->new_rate = $lookup[$grade][$step + 1];
                // $nosi->new_step = $step + 1;
                // $nosi->grade = $grade;
                // $nosi->position_id = $employee->employment_and_compensation->position_id;
                // $nosi->save();

                // create entry in notice of salary adjustment
                // $nosa = new \App\NoticeOfSalaryAdjustment();
                // $nosa->employee_id = $employee->id;
                // $nosa->generated_date = Carbon::now();
                // $nosa->effectivity_date = Carbon::now();
                // $nosa->old_rate = $lookup[$grade][$step];
                // $nosa->new_rate = $lookup[$grade][$step + 1];
                // $nosa->old_step = $step;
                // $nosa->new_step = $step + 1;
                // $nosa->old_grade = $grade;
                // $nosa->new_grade = $grade;
                // $nosa->old_position_id = $employee->employment_and_compensation->position_id;
                // $nosa->new_position_id = $employee->employment_and_compensation->position_id;
                // $nosa->save();

                // create employee history
                // $emp_hist = new \App\EmploymentHistory();
                // $emp_hist->employee_id = $employee->id;
                // $emp_hist->position_id = $employee->employment_and_compensation->position_id;
                // $emp_hist->department_id = $employee->employment_and_compensation->department_id;
                // $emp_hist->start_date = $employee->employment_and_compensation->job_info_effectivity_date;
                // $emp_hist->end_date = $nosi->effectivity_date;
                // $emp_hist->status = $employee->employment_and_compensation->employee_type->employee_type_name;
                // $emp_hist->salary = $nosi->old_rate;
                // $emp_hist->branch = "NPO";
                // $emp_hist->save();
            } else {
                Log::info("- Still within 3 years. Will do nothing");
            }
        }

        Log::info("Finished ProcessStepIncrement Job...");
    }
}
