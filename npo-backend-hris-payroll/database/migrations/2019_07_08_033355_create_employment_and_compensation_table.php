<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmploymentAndCompensationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employment_and_compensation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id');
            $table->string('id_number')->nullable();
            $table->integer('position_id')->nullable();
            $table->date('job_info_effectivity_date')->nullable();
            $table->date('work_sched_effectivity_date')->nullable();
            $table->integer('department_id')->nullable();
            $table->string('section')->nullable();
            $table->integer('employee_type_id')->nullable();
            $table->date('date_hired')->nullable();
            $table->integer('salary_grade_id')->nullable();
            $table->integer('step_increment')->nullable();
            $table->integer('work_schedule_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('sss_number')->nullable();
            $table->string('pagibig_number')->nullable();
            $table->string('gsis_number')->nullable();
            $table->string('philhealth_number')->nullable();
            $table->string('tin')->nullable();
            $table->integer('direct_report_id')->nullable();
            $table->integer('employment_history_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employment_and_compensation');
    }
}
