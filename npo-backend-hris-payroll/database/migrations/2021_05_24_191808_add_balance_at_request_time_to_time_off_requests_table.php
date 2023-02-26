<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBalanceAtRequestTimeToTimeOffRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            $table->float('balance_at_request_time')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            $table->dropColumn('balance_at_request_time');
        });
    }
}
