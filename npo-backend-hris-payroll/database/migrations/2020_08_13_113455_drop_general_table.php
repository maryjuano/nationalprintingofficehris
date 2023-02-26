<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropGeneralTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::dropIfExists('general');
    Schema::table('employees', function (Blueprint $table) {
      $table->dropColumn('general_id');
      $table->dropColumn('employment_and_compensation_id');
      $table->dropColumn('system_information_id');
      $table->dropColumn('documents_attachments_id');
      $table->dropColumn('time_off_balance_id');
    });
    Schema::table('personal_information', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
    Schema::table('family_background', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
    Schema::table('educational_background', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
    Schema::table('civil_service', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
    Schema::table('work_experience', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
    Schema::table('voluntary_work', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
    Schema::table('training_program', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
    Schema::table('other_information', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
    Schema::table('questionnaires', function (Blueprint $table) {
      $table->renameColumn('general_id', 'employee_id');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::create('general', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->integer('employee_id');
      $table->integer('personal_information_id')->nullable();
      $table->integer('family_background_id')->nullable();
      $table->integer('educational_background_id')->nullable();
      $table->integer('civil_service_id')->nullable();
      $table->integer('work_experience_id')->nullable();
      $table->integer('voluntary_work_id')->nullable();
      $table->integer('dev_programs_attended_id')->nullable();
      $table->integer('other_information_id')->nullable();
      $table->timestamps();
    });
    Schema::table('personal_information', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('family_background', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('educational_background', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('civil_service', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('work_experience', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('voluntary_work', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('training_program', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('other_information', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('questionnaires', function (Blueprint $table) {
      $table->renameColumn('employee_id', 'general_id');
    });
    Schema::table('employees', function (Blueprint $table) {
      $table->integer('general_id')->nullable();
      $table->integer('employment_and_compensation_id')->nullable();
      $table->integer('system_information_id')->nullable();
      $table->integer('document_attachments_id')->nullable();
      $table->integer('time_off_balance_id')->nullable();
    });
  }
}
