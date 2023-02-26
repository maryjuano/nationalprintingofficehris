<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeIdIndices extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('personal_information', function (Blueprint $table) {
      $table->index('employee_id');
    });
    Schema::table('employee_stub', function (Blueprint $table) {
      $table->index('employee_id');
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->index('employee_id');
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->index('section');
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->index('employee_type_id');
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->index('department_id');
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->index('position_id');
    });

    Schema::table('salaries', function (Blueprint $table) {
      $table->index('grade');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('personal_information', function (Blueprint $table) {
      $table->dropIndex(['employee_id']);
    });
    Schema::table('employee_stub', function (Blueprint $table) {
      $table->dropIndex(['employee_id']);
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->dropIndex(['employee_id']);
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->dropIndex(['section']);
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->dropIndex(['employee_type_id']);
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->dropIndex(['department_id']);
    });
    Schema::table('employment_and_compensation', function (Blueprint $table) {
      $table->dropIndex(['position_id']);
    });

    Schema::table('salaries', function (Blueprint $table) {
      $table->dropIndex(['grade']);
    });
  }
}
