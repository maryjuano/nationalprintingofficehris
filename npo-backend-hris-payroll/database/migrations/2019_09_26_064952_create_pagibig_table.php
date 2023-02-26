<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreatePagibigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagibig', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->float('minimum_range');
            $table->float('maximum_range');
            $table->float('personal_share');
            $table->float('government_share');
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
        Schema::dropIfExists('pagibig');
    }
}
