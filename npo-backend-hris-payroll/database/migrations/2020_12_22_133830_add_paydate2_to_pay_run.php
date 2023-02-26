<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaydate2ToPayRun extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('payroll_run', function (Blueprint $table) {
      $table->dateTime('payroll_date_2');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('payroll_run', function (Blueprint $table) {
      $table->dropColumn('payroll_date_2');
    });
  }
}
