<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('approval_flow_id')->nullable();
            $table->integer('approval_flow_levels_id')->nullable();
            $table->integer('users_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->boolean('to_be_notified')->default(0);
            $table->boolean('can_approve')->default(0);
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('approval_users');
    }
}
