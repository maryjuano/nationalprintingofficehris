<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePayrollEmployeeLogsAmountToDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_history_for_all_employee', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_history_for_all_employee', function (Blueprint $table) {
            $table->integer('amount')->change();
        });
    }
}
