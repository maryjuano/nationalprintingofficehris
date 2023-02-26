<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixAdjustmentsInProduction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // update the names
        // Allowance (PERA) --> Pera Allowance
        $adj = \App\Adjustment::where('id',8)->first();
        if (!$adj) {
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
        } else {
            $adj->adjustment_name = \App\Adjustment::CONST_PERA_ALLOWANCE;
            $adj->default_amount = 2000;
            $adj->tax = \App\Adjustment::CONST_TAXABLE;
            $adj->save();
        }
        
        // Napowa Dues -> Napowa Due
        $adj = \App\Adjustment::where('id',12)->first();
        if (!$adj) {
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
        } else {
            $adj->adjustment_name = \App\Adjustment::CONST_NAPOWA_DUE;
            $adj->tax = \App\Adjustment::CONST_TAXABLE;
            $adj->save();
        }

        // Delete some entries
        \App\Adjustment::whereIn('id', [6,11])->delete();

        // add new ones not yet present
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

        // set all that should be read-only


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
