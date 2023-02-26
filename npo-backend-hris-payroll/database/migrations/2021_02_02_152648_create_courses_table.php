<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('courses', function (Blueprint $table) {
      $table->id();
      $table->string('course_name');
      $table->integer('course_type');
      $table->boolean('status')->default(true);
      $table->timestamps();
    });

    $existing_courses = \DB::table('educational_background')
      ->whereNotNull('course')
      ->whereIn('type', [3, 4, 5])
      ->groupBy('course')
      ->get();

    foreach ($existing_courses as $data) {
      $course = new \App\Courses();
      $course->course_name = $data->course;
      $course->course_type = $data->type;
      $course->save();
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('courses');
  }
}
