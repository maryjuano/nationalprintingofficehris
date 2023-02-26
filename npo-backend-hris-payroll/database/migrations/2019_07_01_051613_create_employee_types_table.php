<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateEmployeeTypesTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('employee_types', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->string('employee_type_name')->nullable();
      $table->boolean('is_active')->default(true);
      $table->string('created_by');
      $table->string('updated_by');
      $table->timestamps();
    });

    $user_id = DB::table('employee_types')->insertGetId([
      'created_at' => Carbon::now(),
      'employee_type_name' => 'Regular',
      'is_active' => true,
      'created_by' => 'Administrator',
      'updated_by' => 'Administrator'
    ]);

    $user_id = DB::table('employee_types')->insertGetId([
      'created_at' => Carbon::now(),
      'employee_type_name' => 'CoS / Job Order',
      'is_active' => true,
      'created_by' => 'Administrator',
      'updated_by' => 'Administrator'
    ]);

    $user_id = DB::table('employee_types')->insertGetId([
      'created_at' => Carbon::now(),
      'employee_type_name' => 'Probationary',
      'is_active' => true,
      'created_by' => 'Administrator',
      'updated_by' => 'Administrator'
    ]);

    $user_id = DB::table('employee_types')->insertGetId([
      'created_at' => Carbon::now(),
      'employee_type_name' => 'Trainee / OJT',
      'is_active' => true,
      'created_by' => 'Administrator',
      'updated_by' => 'Administrator'
    ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('employee_types');
  }
}
