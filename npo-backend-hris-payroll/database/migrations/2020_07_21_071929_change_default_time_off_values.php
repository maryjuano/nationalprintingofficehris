<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDefaultTimeOffValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('time_offs')
            ->where('time_off_code', 'CNA')
            ->update(['balance_credit' => '3']);
        DB::table('time_offs')
            ->where('time_off_code', 'SPL')
            ->update(['balance_credit' => '5']);
        DB::table('time_offs')
            ->where('time_off_code', 'VAWL')
            ->update(['balance_credit' => '10']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('time_offs')
            ->where('time_off_code', 'CNA')
            ->update(['balance_credit' => '5']);
        DB::table('time_offs')
            ->where('time_off_code', 'SPL')
            ->update(['balance_credit' => '3']);
        DB::table('time_offs')
            ->where('time_off_code', 'VAWL')
            ->update(['balance_credit' => 11]);
    }
}
