<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateEmployeeHistory extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::dropIfExists('employment_history');
    Schema::create('employment_history', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->timestamps();
      $table->integer('employee_id');
      $table->integer('position_id');
      $table->integer('department_id');
      $table->date('start_date');
      $table->date('end_date');
      $table->string('status')->nullable();
      $table->string('salary')->nullable();
      $table->string('tranche_version')->nullable();
      $table->string('branch')->nullable();
      $table->string('lwop')->nullable();
      $table->date('separation_date')->nullable();
      $table->text('separation_cause')->nullable();
      $table->string('separation_amount_received')->nullable();
      $table->text('remarks')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('employment_history');
  }
}
