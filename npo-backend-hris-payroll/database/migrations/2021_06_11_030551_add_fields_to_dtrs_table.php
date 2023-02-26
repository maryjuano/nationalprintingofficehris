<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToDtrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->decimal('late_for_vl_deduction', 11, 3)->default(0.00);
            $table->integer('absence_for_vl_deduction')->default(0);
            $table->integer('absence_for_sl_deduction')->default(0);
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
            $table->dropColumn('absence_for_sl_deduction');
            $table->dropColumn('absence_for_vl_deduction');
            $table->dropColumn('late_for_vl_deduction');
        });
    }
}
