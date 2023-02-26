<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInclusionsToPayrollRunTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_run', function (Blueprint $table) {
            $table->longText('other_inclusion_1');
            $table->longText('other_inclusion_2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_run', function (Blueprint $table) {
            $table->dropColumn('other_inclusion_1');
            $table->dropColumn('other_inclusion_2');
        });
    }
}
