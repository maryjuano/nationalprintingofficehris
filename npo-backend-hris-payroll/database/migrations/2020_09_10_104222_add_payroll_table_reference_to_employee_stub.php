<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPayrollTableReferenceToEmployeeStub extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_stub', function (Blueprint $table) {
            $table->unsignedInteger('payroll_run_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_stub', function (Blueprint $table) {
            $table->dropColumn('payroll_run_id');
        });
    }
}
