<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrecisionToDtrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->decimal('late_for_vl_deduction', 18, 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->decimal('late_for_vl_deduction', 11, 3)->change();
        });
    }
}
