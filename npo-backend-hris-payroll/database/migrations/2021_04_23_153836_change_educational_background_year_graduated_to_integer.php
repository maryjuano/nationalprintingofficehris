<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEducationalBackgroundYearGraduatedToInteger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_background', function (Blueprint $table) {
            $table->integer('year_graduated')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('educational_background', function (Blueprint $table) {
            $table->date('year_graduated')->nullable()->change();
        });
    }
}
