<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SeperateEducBackgroundYMD extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('educational_background', function (Blueprint $table) {
      $table->integer('start_year')->default(0);
      $table->integer('start_month');
      $table->integer('start_day');
      $table->integer('end_year')->default(0);
      $table->integer('end_month');
      $table->integer('end_day');
    });

    $dates = DB::table('educational_background')->select('start_period', 'end_period', 'id')->get();

    foreach ($dates as $date) {
      if (!is_null($date->start_period)) {
        $startDate = strtotime($date->start_period);
        DB::table('educational_background')->where('id', $date->id)->update([
          'start_year' => date('Y', $startDate),
          'start_month' => date('n', $startDate),
          'start_day' => date('j', $startDate)
        ]);
      }
      if (!is_null($date->end_period)) {
        $endDate = strtotime($date->end_period);
        DB::table('educational_background')->where('id', $date->id)->update([
          'end_year' => date('Y', $endDate),
          'end_month' => date('n', $endDate),
          'end_day' => date('j', $endDate)
        ]);
      }
    }

    Schema::table('educational_background', function (Blueprint $table) {
      $table->dropColumn('start_period');
      $table->dropColumn('end_period');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('educational_background', function (Blueprint $table) {
      $table->date('start_period');
      $table->date('end_period');
    });
    $dates = DB::table('educational_background')
      ->select(
        'start_year',
        'start_month',
        'start_day',
        'end_year',
        'end_month',
        'end_day',
        'id'
      )->get();

    foreach ($dates as $date) {
      if ($date->start_year !== 0) {
        $startDate = $date->start_year . '-' . $date->start_month . '-' . $date->start_day;
        DB::table('educational_background')->where('id', $date->id)->update([
          'start_period' => date($startDate),
        ]);
      }
      if ($date->end_year !== 0) {
        $endDate = $date->end_year . '-' . $date->end_month . '-' . $date->end_day;
        DB::table('educational_background')->where('id', $date->id)->update([
          'end_period' => date($endDate),
        ]);
      }
    }
    Schema::table('educational_background', function (Blueprint $table) {
      $table->dropColumn('start_year');
      $table->dropColumn('start_month');
      $table->dropColumn('start_day');
      $table->dropColumn('end_year');
      $table->dropColumn('end_month');
      $table->dropColumn('end_day');
    });
  }
}
