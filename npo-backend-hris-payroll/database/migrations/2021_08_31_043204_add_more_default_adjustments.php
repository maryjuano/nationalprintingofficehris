<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreDefaultAdjustments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_PEI,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_NON_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 10000,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_CLOTHING_ALLOWANCE,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_NON_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 6000,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();
        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_CASH_GIFT,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_NON_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 5000,
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
