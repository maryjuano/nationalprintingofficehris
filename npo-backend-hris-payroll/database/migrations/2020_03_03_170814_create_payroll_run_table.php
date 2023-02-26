<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayrollRunTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_run', function (Blueprint $table) {
            $table->increments('id');
            $table->string('payroll_name');
            $table->integer('payroll_type');
            /** 0-daily, 1-semimonthly, 2-monthly **/
            $table->dateTime('payroll_period_start');
            $table->dateTime('payroll_period_end');
            $table->dateTime('payroll_date');
            $table->dateTime('deduction_start');
            $table->dateTime('deduction_end');
            $table->longText('other_inclusion');
            /** array 1 - DTR (includes all except OT), 2- OT, 3-Adjustments, 4-Contributions, 5 - Tax, 6 - Statutory) **/
            $table->integer('status');
            /** 0 - draft, 1- simulated , 2 - completed **/
            $table->longText('employee_ids');
            /** array for employee ids **/
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
        Schema::dropIfExists('payroll_run');
    }
}
