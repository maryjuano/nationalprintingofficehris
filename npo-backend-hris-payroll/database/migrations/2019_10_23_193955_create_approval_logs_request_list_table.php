<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalLogsRequestListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_logs_request_list', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id');
            $table->integer('request_id');
            $table->integer('request_type_id');
            $table->integer('approval_flow_id');
            $table->integer('approval_levels_id');
            $table->integer('approval_users');
            $table->integer('is_approved');
            $table->string('is_remarks')->nullable();
            $table->boolean('is_next');
            $table->boolean('is_in_order')->default(0);
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_logs_request_list');
    }
}
