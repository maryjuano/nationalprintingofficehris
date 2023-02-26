<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Adjustment;

class AddHiddenToAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adjustments', function (Blueprint $table) {
            $table->integer('is_hidden')->default(false);
        });

        // insert relevant data
        // add new values
        $item = new Adjustment();
        $item->fill(array(
            'adjustment_name' => Adjustment::CONST_PREMIUM,
            'type' => Adjustment::CONST_TYPE_EARNINGS,
            'tax' => Adjustment::CONST_TAXABLE,
            'category' => Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 10,
            'read_only' => true,
            'is_hidden' => true
        ));
        $item->save();

        $item = new Adjustment();
        $item->fill(array(
            'adjustment_name' => Adjustment::CONST_TAX_TWO_PERCENT,
            'type' => Adjustment::CONST_TYPE_TAX,
            'tax' => Adjustment::CONST_TAXABLE,
            'category' => Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 2,
            'read_only' => true,
            'is_hidden' => true
        ));
        $item->save();

        $item = new Adjustment();
        $item->fill(array(
            'adjustment_name' => Adjustment::CONST_TAX_THREE_PERCENT,
            'type' => Adjustment::CONST_TYPE_TAX,
            'tax' => Adjustment::CONST_TAXABLE,
            'category' => Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 3,
            'read_only' => true,
            'is_hidden' => true
        ));
        $item->save();

        $item = new Adjustment();
        $item->fill(array(
            'adjustment_name' => Adjustment::CONST_TAX,
            'type' => Adjustment::CONST_TYPE_TAX,
            'tax' => Adjustment::CONST_TAXABLE,
            'category' => Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => true
        ));
        $item->save();

        $item = new Adjustment();
        $item->fill(array(
            'adjustment_name' => Adjustment::CONST_PAG_IBIG,
            'type' => Adjustment::CONST_TYPE_STATUTORY,
            'tax' => Adjustment::CONST_TAXABLE,
            'category' => Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => true
        ));
        $item->save();

        $item = new Adjustment();
        $item->fill(array(
            'adjustment_name' => Adjustment::CONST_GSIS,
            'type' => Adjustment::CONST_TYPE_STATUTORY,
            'tax' => Adjustment::CONST_TAXABLE,
            'category' => Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => true
        ));
        $item->save();

        $item = new Adjustment();
        $item->fill(array(
            'adjustment_name' => Adjustment::CONST_PHILHEALTH,
            'type' => Adjustment::CONST_TYPE_STATUTORY,
            'tax' => Adjustment::CONST_TAXABLE,
            'category' => Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => true
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
        Schema::table('adjustments', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
}
