<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTopOfIdToPayrollHistoryForAllEmployee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_history_for_all_employee', function (Blueprint $table) {
            $table->integer('type_of_id')->nullable();
        });

        $logs = \App\PayrollEmployeeLog::all();
        $adjustments = \App\Adjustment::all();
        $adjustment_lookup = $adjustments->keyBy('adjustment_name');
        foreach ($logs as $log) {
            if ($log->type_of == 1) { // earnings
                if (isset($adjustment_lookup[$log->type_of_string])) {
                    $log->type_of_id = $adjustment_lookup[$log->type_of_string]->id;
                    $log->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_history_for_all_employee', function (Blueprint $table) {
            $table->dropColumn('type_of_id');
        });
    }
}
