<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLwopColumnToTimeOffRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            $table->boolean('is_without_pay')->default(false);
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
            $table->dropColumn('is_without_pay');
        });
    }
}
