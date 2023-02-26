<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeOffRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->integer('time_off_type')->nullable();
            $table->integer('multiple_days')->nullable();
            $table->float('total_days')->nullable();
            $table->integer('is_within_Philippines')->nullable();
            $table->string('remarks')->nullable();
            $table->string('location')->nullable();
            $table->integer('status')->default(0);
            $table->string('updated_by')->nullable();
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
        Schema::dropIfExists('time_off_requests');
    }
}
