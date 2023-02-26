<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToEmpComp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employment_and_compensation', function (Blueprint $table) {
            $table->string('position_name')->nullable();
            $table->date('period_of_service_start')->nullable();
            $table->date('period_of_service_end')->nullable();
            $table->date('start_date')->nullable();
            $table->float('salary_rate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employment_and_compensation', function (Blueprint $table) {
            $table->dropColumn('position_name');
            $table->dropColumn('period_of_service_start');
            $table->dropColumn('period_of_service_end');
            $table->dropColumn('start_date');
            $table->dropColumn('salary_rate');
        });
    }
}
