<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Makerefnull extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('references', function (Blueprint $table) {
            $table->string('ref_name')->nullable()->change();
            $table->string('ref_address')->nullable()->change();
            $table->string('ref_tel_no')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('references', function (Blueprint $table) {
            $table->string('ref_name')->change();
            $table->string('ref_address')->change();
            $table->string('ref_tel_no')->change();
        });
    }
}
