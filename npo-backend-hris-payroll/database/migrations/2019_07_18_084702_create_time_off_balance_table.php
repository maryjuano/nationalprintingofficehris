<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeOffBalanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_off_balance', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id');
            $table->integer('time_off_id')->nullable();
            $table->float('number_of_days', 6, 3);
            $table->float('points', 6, 3);
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
        Schema::dropIfExists('time_off_balance');
    }
}
