<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNextDayToBreakTimes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('break_times', function (Blueprint $table) {
            $table->boolean('start_time_next_day')->default(false);
            $table->boolean('end_time_next_day')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('break_times', function (Blueprint $table) {
            $table->dropColumn('start_time_next_day');
            $table->dropColumn('end_time_next_day');
        });
    }
}
