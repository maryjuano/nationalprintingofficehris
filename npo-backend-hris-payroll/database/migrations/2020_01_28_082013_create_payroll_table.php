<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayrollTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('startDate');
            $table->dateTime('endDate');
            $table->longText('pay_structure');
            $table->integer('status');
            $table->integer('employeeId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll', function (Blueprint $table) {
            //
        });
    }
}
