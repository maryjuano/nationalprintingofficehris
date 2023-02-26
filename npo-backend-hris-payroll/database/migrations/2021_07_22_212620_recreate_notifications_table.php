<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('notifications');
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id');
            $table->integer('status');
            $table->string('section');
            $table->string('source');
            $table->integer('source_id');
            $table->longText('message');
            $table->longText('payload');
            $table->timestamps();

            $table->index('section');
            $table->index('source');
            $table->index('employee_id');
        });



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
