<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionnairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('general_id');
            $table->boolean('third_degree_relative')->nullable();
            $table->string('third_degree_relative_details')->nullable();
            $table->boolean('fourth_degree_relative')->nullable();
            $table->string('fourth_degree_relative_details')->nullable();
            $table->boolean('administrative_offender')->nullable();
            $table->string('administrative_offender_details')->nullable();
            $table->boolean('criminally_charged')->nullable();
            $table->longtext('criminally_charged_data')->nullable();
            $table->boolean('convicted_of_crime')->nullable();
            $table->string('convicted_of_crime_details')->nullable();
            $table->boolean('separated_from_service')->nullable();
            $table->string('separated_from_service_details')->nullable();
            $table->boolean('election_candidate')->nullable();
            $table->string('election_candidate_details')->nullable();
            $table->boolean('resigned_from_gov')->nullable();
            $table->string('resigned_from_gov_details')->nullable();
            $table->boolean('multiple_residency')->nullable();
            $table->string('multiple_residency_country')->nullable();
            $table->boolean('indigenous')->nullable();
            $table->string('indigenous_group')->nullable();
            $table->boolean('pwd')->nullable();
            $table->integer('pwd_id')->nullable();
            $table->boolean('solo_parent')->nullable();
            $table->integer('solo_parent_id')->nullable();
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
        Schema::dropIfExists('questionnaires');
    }
}
