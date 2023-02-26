<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeneralTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('general');
    }
}
