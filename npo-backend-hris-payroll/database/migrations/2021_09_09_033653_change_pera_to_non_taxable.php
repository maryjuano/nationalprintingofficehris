<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePeraToNonTaxable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('non_taxable', function (Blueprint $table) {
            $item = \App\Adjustment::where('adjustment_name', \App\Adjustment::CONST_PERA_ALLOWANCE)->first();
            $item->tax = \App\Adjustment::CONST_NON_TAXABLE;
            $item->ceiling = 999999;
            $item->save();

            $item = \App\Adjustment::where('adjustment_name', \App\Adjustment::CONST_OPAID_ALLOWANCE)->first();
            $item->tax = \App\Adjustment::CONST_NON_TAXABLE;
            $item->save();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('non_taxable', function (Blueprint $table) {
            //
        });
    }
}
