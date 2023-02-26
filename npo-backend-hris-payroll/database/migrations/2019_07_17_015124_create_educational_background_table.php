<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEducationalBackgroundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('educational_background', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('general_id');
            $table->integer('type')->nullable();
            $table->string('school_name')->nullable();
            $table->string('course')->nullable();
            $table->date('start_period')->nullable();
            $table->date('end_period')->nullable();
            $table->string('level_or_units')->nullable();
            $table->date('year_graduated')->nullable();
            $table->string('honors')->nullable();
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
        Schema::dropIfExists('educational_background');
    }
}
