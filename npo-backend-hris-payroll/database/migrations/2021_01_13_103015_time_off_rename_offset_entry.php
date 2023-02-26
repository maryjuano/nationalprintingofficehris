<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TimeOffRenameOffsetEntry extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::table('time_offs')
            ->where('time_off_type', 'Off-Set')
            ->update([
                'time_off_type' => 'Compensatory Time Off',
                'time_off_code' => 'CTO'
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::table('time_offs')
            ->where('time_off_type', 'Compensatory Time Off')
            ->update([
                'time_off_type' => 'Off-Set',
                'time_off_code' => 'OFST'
            ]);
    }
}
