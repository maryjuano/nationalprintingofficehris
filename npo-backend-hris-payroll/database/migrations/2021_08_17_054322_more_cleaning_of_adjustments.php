<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoreCleaningOfAdjustments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Delete some entries
        \App\Adjustment::whereIn('id', [14,15,16,3])->delete();
        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_OPAID_REGULAR,
            'type' => \App\Adjustment::CONST_TYPE_DEDUCTIONS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
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
