<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdjustmentsAgain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Adjustment::whereIn('adjustment_name', [
            \App\Adjustment::CONST_OPAID_REGULAR,
            \App\Adjustment::CONST_OPAID_ALLOWANCE,
            \App\Adjustment::CONST_UPAID_SALARY
        ])->delete();
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
