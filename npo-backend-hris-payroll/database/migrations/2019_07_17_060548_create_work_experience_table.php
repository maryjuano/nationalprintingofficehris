<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkExperienceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_experience', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('general_id');
            $table->date('start_inclusive_date')->nullable();
            $table->date('end_inclusive_date')->nullable();
            $table->string('position_title')->nullable();
            $table->string('company')->nullable();
            $table->float('monthly_salary')->nullable();
            $table->integer('pay_grade')->nullable();
            $table->string('status_of_appointment')->nullable();
            $table->boolean('government_service')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_experience');
    }
}
