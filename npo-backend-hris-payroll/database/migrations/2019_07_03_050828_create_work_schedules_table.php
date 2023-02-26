<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->string('work_schedule_name');
            $table->integer('time_option');
            $table->integer('flexible_weekly_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->bigIncrements('id');
            $table->string('created_by');
            $table->dateTime('created_at');
            $table->string('updated_by');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_schedules');
    }
}
