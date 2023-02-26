<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropUnnecessaryColumnsFromLoans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->renameColumn('employee_users_id', 'employee_id');
            $table->dropColumn('employee_name');
            $table->dropColumn('direct_report_id');
            $table->dropColumn('mobile_number');
            $table->dropColumn('email');
            $table->dropColumn('department_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->renameColumn('employee_id', 'employee_users_id');
            $table->string('employee_name');
            $table->integer('direct_report_id'); //reporting manager
            $table->string('mobile_number');
            $table->string('email');
            $table->integer('department_id');
        });
    }
}
