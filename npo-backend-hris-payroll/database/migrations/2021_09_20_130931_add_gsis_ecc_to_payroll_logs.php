<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGsisEccToPayrollLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_history_for_all_employee', function (Blueprint $table) {
            $table->decimal('gsis_ecc', 18, 2)->default(0);
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
            $table->dropColumn('gsis_ecc');
        });
    }
}
