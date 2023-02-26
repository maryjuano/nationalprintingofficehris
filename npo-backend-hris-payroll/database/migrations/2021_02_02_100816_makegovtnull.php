<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Makegovtnull extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('govt_ids', function (Blueprint $table) {
            $table->string('id_type')->nullable()->change();
            $table->string('id_no')->nullable()->change();
            $table->string('place_of_issue')->nullable()->change();
            $table->date('date_of_issue')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('govt_ids', function (Blueprint $table) {
            $table->string('id_type')->change();
            $table->string('id_no')->change();
            $table->string('place_of_issue')->change();
            $table->date('date_of_issue')->change();
        });
    }
}
