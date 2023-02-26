<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoluntaryWorkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voluntary_work', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('general_id');
            $table->string('name_of_organization')->nullable();
            $table->string('address')->nullable();
            $table->date('start_inclusive_date')->nullable();
            $table->date('end_inclusive_date')->nullable();
            $table->integer('number_of_hours')->nullable();
            $table->string('position')->nullable();
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
        Schema::dropIfExists('voluntary_work');
    }
}
