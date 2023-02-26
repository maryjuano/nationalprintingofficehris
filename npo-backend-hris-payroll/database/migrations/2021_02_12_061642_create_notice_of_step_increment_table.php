<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticeOfStepIncrementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notice_of_step_increment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('generated_date');
            $table->dateTime('effectivity_date');
            $table->integer('employee_id');
            $table->double('old_rate', 15, 2);
            $table->double('new_rate', 15, 2);
            $table->integer('new_step');
            $table->integer('grade');
            $table->integer('position_id');
            $table->timestamps();
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notice_of_step_increment');
    }
}
