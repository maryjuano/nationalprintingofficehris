<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPositionCode extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('positions', function (Blueprint $table) {
      $table->dropColumn('position_code');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('positions', function (Blueprint $table) {
      $table->string('position_code')->default('AAAA-000');
    });
  }
}
