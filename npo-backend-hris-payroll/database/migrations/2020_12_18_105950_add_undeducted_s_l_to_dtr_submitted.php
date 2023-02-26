<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUndeductedSLToDtrSubmitted extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dtr_submitted', function (Blueprint $table) {
            $table->double('undeductedSL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtr_submitted', function (Blueprint $table) {
            $table->dropColumn('undeductedSL');
        });
    }
}
