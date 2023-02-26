<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppFlowLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('app_flow_levels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->bigInteger('app_flow_id');
            $table->bigInteger('dependent_on')->default(-1);
            $table->string('description');
            $table->string('selection_mode');
            /** ‘all’ or ‘one’ */
        });
        Schema::create('app_flow_level_employee', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('app_flow_levels_id');
            $table->bigInteger('approver_id');
            $table->boolean('can_approve')->default(false);
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
        Schema::dropIfExists('app_flow_levels');
        Schema::dropIfExists('app_flow_level_employee');
    }
}
