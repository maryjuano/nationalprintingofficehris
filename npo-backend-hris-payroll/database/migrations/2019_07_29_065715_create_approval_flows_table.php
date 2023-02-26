<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->string('approval_flow_name')->nullable();
            $table->integer('section_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('request_id')->nullable();
            $table->boolean('pick_employee')->nullable();
            $table->text('employee_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->integer('status')->nullable();
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
        Schema::dropIfExists('approval_flows');
    }
}
