<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayrollHistoryForAllEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_history_for_all_employee', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id');
            $table->integer('payroll_id');
            $table->date('payroll_period');
            $table->date('payroll_start');
            $table->date('payroll_end');
            $table->integer('day');
            $table->integer('month');
            $table->integer('year');
            $table->integer('type_of');
            $table->integer('amount');
            $table->string('type_of_string');
            $table->string('inclusion_type');
            $table->integer('gross_pay');
            $table->integer('basic_pay');
            $table->integer('net_pay');
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
        Schema::dropIfExists('payroll_history_for_all_employee');
    }
}
