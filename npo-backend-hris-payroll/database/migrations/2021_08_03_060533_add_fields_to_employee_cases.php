<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEmployeeCases extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_cases', function (Blueprint $table) {
            $table->string('case_id')->nullable();
            $table->date('date_of_resolution')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_cases', function (Blueprint $table) {
            $table->dropColumn('case_id');
            $table->dropColumn('date_of_resolution');
        });
    }
}
