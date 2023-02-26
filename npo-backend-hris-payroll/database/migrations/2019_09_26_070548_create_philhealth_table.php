<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhilhealthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('philhealth', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->float('minimum_range');
            $table->float('maximum_range');
            $table->text('personal_share');
            $table->text('government_share');
            $table->text('monthly_premium');
            $table->boolean('is_percentage')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('philhealth');
    }
}
