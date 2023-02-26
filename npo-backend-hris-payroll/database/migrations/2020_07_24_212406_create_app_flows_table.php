<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('app_flows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('request_type');
            $table->integer('department_id');
            $table->integer('section_id');
            $table->boolean('status')->default(true);
            $table->boolean('pick_employee')->default(false);
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('app_flow_employee', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('app_flow_id');
            $table->bigInteger('requestor_id');
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
        Schema::dropIfExists('app_flows');
        Schema::dropIfExists('app_flow_employee');
    }
}
