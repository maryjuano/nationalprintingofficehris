<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeStubTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_stub', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id');
            $table->longText('earnings');
            $table->longText('deductions');
            $table->longText('contribution');
            $table->longText('loan');
            $table->longText('reimbursement');
            $table->integer('status');
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
        Schema::dropIfExists('employee_stub');
    }
}
