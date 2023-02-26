<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsageStatusToOvertimeReqeustsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->bigInteger('duration_in_minutes')->default(0);
        });
        Schema::create('overtime_uses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('overtime_request_id');
            $table->foreign('overtime_request_id')->references('id')->on('overtime_requests');
            $table->bigInteger('duration_in_minutes');
            $table->timestamps();
        });
        Schema::create('overtime_users', function (Blueprint $table) {
            $table->unsignedBigInteger('overtime_use_id');
            $table->unsignedBigInteger('overtime_user_id');
            $table->string('overtime_user_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropColumn('duration_in_minutes');
        });
        Schema::dropIfExists('overtime_uses');
        Schema::dropIfExists('overtime_users');
    }
}
