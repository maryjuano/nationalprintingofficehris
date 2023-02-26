<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeOffRequestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_off_request_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('time_off_request_id');
            $table->date('time_off_date');
            $table->string('time_off_duration');
            $table->string('time_off_period')->nullable();
            // $table->time('time_will_be_gone')->nullable();
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
        Schema::dropIfExists('time_off_request_details');
    }
}
