<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TaxAdjustmentsFix extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tax = \App\Tax::where('id', 1)->first();
        if ($tax) {
            $tax->delete();
        }
        $tax = \App\Tax::where('id', 2)->first();
        if ($tax) {
            $tax->delete();
        }

        \App\Adjustment::truncate();
        // insert relevant data
        // add new values
        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_PREMIUM,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 10,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_TAX_TWO_PERCENT,
            'type' => \App\Adjustment::CONST_TYPE_TAX,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 2,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_TAX_THREE_PERCENT,
            'type' => \App\Adjustment::CONST_TYPE_TAX,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 3,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_TAX,
            'type' => \App\Adjustment::CONST_TYPE_TAX,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_PAG_IBIG,
            'type' => \App\Adjustment::CONST_TYPE_STATUTORY,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_GSIS,
            'type' => \App\Adjustment::CONST_TYPE_STATUTORY,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_PHILHEALTH,
            'type' => \App\Adjustment::CONST_TYPE_STATUTORY,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_PERA_ALLOWANCE,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 2000,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_NAPOWA_DUE,
            'type' => \App\Adjustment::CONST_TYPE_STATUTORY,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 30,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_MID_YEAR_BONUS,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_NON_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_YEAR_END_BONUS,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_NON_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_LATE,
            'type' => \App\Adjustment::CONST_TYPE_DEDUCTIONS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_UNDERTIME,
            'type' => \App\Adjustment::CONST_TYPE_DEDUCTIONS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_ABSENCE,
            'type' => \App\Adjustment::CONST_TYPE_DEDUCTIONS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_OPAID_ALLOWANCE,
            'type' => \App\Adjustment::CONST_TYPE_DEDUCTIONS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_OVERTIME,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

        $item = new \App\Adjustment();
        $item->fill(array(
            'adjustment_name' => \App\Adjustment::CONST_UPAID_SALARY,
            'type' => \App\Adjustment::CONST_TYPE_EARNINGS,
            'tax' => \App\Adjustment::CONST_TAXABLE,
            'category' => \App\Adjustment::CONST_CATEGORY_REGULAR,
            'ceiling' => 0,
            'default_amount' => 0,
            'read_only' => true,
            'is_hidden' => false
        ));
        $item->save();

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
