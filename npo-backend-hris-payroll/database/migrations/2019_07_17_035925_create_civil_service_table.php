<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCivilServiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('civil_service', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('general_id');
            $table->string('government_id')->nullable();
            $table->date('date')->nullable();
            //$table->integer('date_rating')->nullable();
            $table->string('place')->nullable();
            $table->string('license_no')->nullable();
            $table->date('validity_date')->nullable();
            $table->integer('place_rating')->nullable();
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
        Schema::dropIfExists('civil_service');
    }
}
