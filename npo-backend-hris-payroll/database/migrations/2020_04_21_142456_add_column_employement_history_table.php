<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnEmployementHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employment_history', function (Blueprint $table) {
            $table->string('branch');
            $table->dateTime('start_LWOP');
            $table->dateTime('end_LWOP');
            $table->longText('remarks');
            $table->dateTime('separation_date');
            $table->longText('separation_cause');
            $table->double('separation_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employment_history');
    }
}
