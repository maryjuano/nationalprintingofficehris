<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToTaxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tax = new \App\Tax();
        $tax->lowerLimit = 2000000;
        $tax->upperLimit = 8000000;
        $tax->constant = 490000;
        $tax->percentage = 32;
        $tax->class = '2020-01-01 17:37:49';
        $tax->save();

        $tax = new \App\Tax();
        $tax->lowerLimit = 8000000;
        $tax->upperLimit = 99999999;
        $tax->constant = 2410000;
        $tax->percentage = 35;
        $tax->class = '2020-01-01 17:37:49';
        $tax->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tax', function (Blueprint $table) {
            //
        });
    }
}
