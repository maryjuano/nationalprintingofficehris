<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToOtRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ot_request', function (Blueprint $table) {
            $table->string('requested_by')->nullable();
            $table->longText('schedule')->nullable();
            $table->boolean('is_requested');
            $table->text('remarks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ot_request', function (Blueprint $table) {
            $table->dropColumn('requested_by');
            $table->dropColumn('schedule');
            $table->dropColumn('is_requested');
            $table->dropColumn('remarks');
        });
    }
}
