<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFixedDailyTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fixed_daily_times', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('work_schedule_id');
            $table->string('start_times');
            $table->string('end_times');
            $table->string('grace_periods');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fixed_daily_times');
    }
}
