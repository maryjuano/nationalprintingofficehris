<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOldStepToNoticeOfStepIncrementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notice_of_step_increment', function (Blueprint $table) {
            $table->integer('old_step');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notice_of_step_increment', function (Blueprint $table) {
            $table->dropColumn('old_step');
        });
    }
}
