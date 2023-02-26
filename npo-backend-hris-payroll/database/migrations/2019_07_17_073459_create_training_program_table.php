<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingProgramTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_program', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('general_id');
            $table->string('title')->nullable();
            $table->date('start_inclusive_date')->nullable();
            $table->date('end_inclusive_date')->nullable();
            $table->integer('number_of_hours')->nullable();
            $table->string('type')->nullable();
            $table->string('sponsor')->nullable();
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
        Schema::dropIfExists('training_program');
    }
}
