<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameUserIdToEmployeeId extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('edit_requests', function (Blueprint $table) {
      $table->renameColumn('user_id', 'employee_id');
    });
    Schema::table('time_off_requests', function (Blueprint $table) {
      $table->renameColumn('user_id', 'employee_id');
      $table->renameColumn('time_off_type', 'time_off_balance_id');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('edit_requests', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'user_id');
    });
    Schema::table('time_off_requests', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'user_id');
      $table->renameColumn('time_off_balance_id', 'time_off_type');
    });
  }
}
