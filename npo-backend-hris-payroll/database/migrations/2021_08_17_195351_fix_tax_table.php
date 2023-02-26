<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixTaxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tax = \App\Tax::where('upperLimit', 400000)->first();
        if ($tax) {
            $tax->lowerLimit = 250000;
            $tax->constant = 0;
            $tax->save();
        }
        
        $tax = \App\Tax::where('upperLimit', 800000)->first();
        if ($tax) {
            $tax->lowerLimit = 400000;
            $tax->constant = 30000;
            $tax->save();
        }

        $tax = \App\Tax::where('upperLimit', 2000000)->first();
        if ($tax) {
            $tax->lowerLimit = 800000;
            $tax->constant = 130000;
            $tax->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
