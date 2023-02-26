<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('users_id')->nullable();
            $table->integer('general_id')->nullable();
            $table->integer('employment_and_compensation_id')->nullable();
            $table->integer('system_information_id')->nullable();
            $table->integer('documents_attachments_id')->nullable();
            $table->integer('time_off_balance_id')->nullable();
            $table->boolean('status')->default(false);
            $table->string('created_by')->nullable();
            $table->dateTime('created_at');
            $table->string('updated_by')->nullable();
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
        Schema::dropIfExists('employees');
    }
}
