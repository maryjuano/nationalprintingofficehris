<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_levels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('approval_flow_id')->nullable();
            $table->string('description')->nullable();
            $table->integer('in_order')->default(0);
            $table->integer('order_option')->default(0);
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
        Schema::dropIfExists('approval_levels');
    }
}
