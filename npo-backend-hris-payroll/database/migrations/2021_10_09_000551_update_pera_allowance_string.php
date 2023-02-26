<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePeraAllowanceString extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $item = \App\Adjustment::where('adjustment_name', 'Pera Allowance')->first();
        $item->adjustment_name = \App\Adjustment::CONST_PERA_ALLOWANCE;
        $item->save();
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
