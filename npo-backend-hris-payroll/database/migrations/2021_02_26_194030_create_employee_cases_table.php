<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeCasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_cases', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('employee_id');
            $table->text('type');
            $table->text('title');
            $table->date('date_filed')->nullable();
            $table->date('status_effective_date')->nullable();
            $table->longText('remarks')->nullable();
            $table->text('status');
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->bigInteger('employee_case_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_cases');
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('employee_case_id');
        });
    }
}
