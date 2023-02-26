<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOvertimeRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('employee_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('remarks')->nullable();
            $table->integer('status')->default(-2);
            $table->bigInteger('approval_request_id');
            $table->bigInteger('authority_to_ot_id')->nullable();
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
        Schema::dropIfExists('overtime_requests');
    }
}
