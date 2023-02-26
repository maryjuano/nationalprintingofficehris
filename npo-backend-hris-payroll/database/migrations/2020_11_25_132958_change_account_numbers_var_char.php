<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeAccountNumbersVarChar extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->string('pagibig_number')->change();
      $table->string('gsis_number')->change();
      $table->string('philhealth_number')->change();
      $table->string('tin')->change();
      $table->string('sss_number')->change();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->integer('pagibig_number')->change();
      $table->integer('gsis_number')->change();
      $table->integer('philhealth_number')->change();
      $table->integer('tin')->change();
      $table->integer('sss_number')->change();
    });
  }
}
