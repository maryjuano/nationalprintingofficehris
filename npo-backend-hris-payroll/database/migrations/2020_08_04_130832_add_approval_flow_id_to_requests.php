<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApprovalFlowIdToRequests extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    DB::table('loan_requests')->truncate();
    Schema::table('loan_requests', function (Blueprint $table) {
      $table->bigInteger('approval_request_id');
      $table->dropColumn('approval_flow_id');
    });
    DB::table('contribution_request')->truncate();
    Schema::table('contribution_request', function (Blueprint $table) {
      $table->bigInteger('approval_request_id');
    });
    DB::table('ot_request')->truncate();
    Schema::table('ot_request', function (Blueprint $table) {
      $table->bigInteger('approval_request_id');
    });
    DB::table('time_off_requests')->truncate();
    Schema::table('time_off_requests', function (Blueprint $table) {
      $table->bigInteger('approval_request_id');
    });
    DB::table('dtr_submitted')->truncate();
    Schema::table('dtr_submitted', function (Blueprint $table) {
      $table->bigInteger('approval_request_id');
    });
    DB::table('document_request')->truncate();
    Schema::table('document_request', function (Blueprint $table) {
      $table->bigInteger('approval_request_id');
    });
    DB::table('edit_requests')->truncate();
    Schema::table('edit_requests', function (Blueprint $table) {
      $table->bigInteger('approval_request_id');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('contribution_request', function (Blueprint $table) {
      $table->dropColumn('approval_request_id');
    });
    Schema::table('edit_requests', function (Blueprint $table) {
      $table->dropColumn('approval_request_id');
    });
    Schema::table('ot_request', function (Blueprint $table) {
      $table->dropColumn('approval_request_id');
    });
    Schema::table('time_off_requests', function (Blueprint $table) {
      $table->dropColumn('approval_request_id');
    });
    Schema::table('dtr_submitted', function (Blueprint $table) {
      $table->dropColumn('approval_request_id');
    });
    Schema::table('document_request', function (Blueprint $table) {
      $table->dropColumn('approval_request_id');
    });
    Schema::table('loan_requests', function (Blueprint $table) {
      $table->dropColumn('approval_request_id');
      $table->bigInteger('approval_flow_id');
    });
  }
}
