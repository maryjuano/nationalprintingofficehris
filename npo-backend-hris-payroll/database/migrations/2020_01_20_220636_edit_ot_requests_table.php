<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditOtRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ot_request', function (Blueprint $table) {
            $table->datetime('start')->change();
            $table->datetime('end')->change();
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
            $table->date('start')->change();
            $table->date('end')->change();
        });
    }
}
