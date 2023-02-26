<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->string('position_name');
            $table->string('position_code');
            $table->integer('department_id');
            $table->integer('salary_grade');
            $table->boolean('is_active')->default(true);
            $table->boolean('vacancy')->default(true);
            $table->bigIncrements('id');
            $table->string('item_number');
            $table->string('created_by');
            $table->dateTime('created_at');
            $table->string('updated_by');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('positions');
    }
}
