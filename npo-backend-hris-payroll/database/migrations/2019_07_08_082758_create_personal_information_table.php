<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalInformationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_information', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('general_id');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name_extension')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->integer('civil_status')->nullable();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('blood_type')->nullable();
            $table->integer('gender')->nullable();
            $table->string('citizenship')->nullable();
            $table->integer('dual_citizenship')->nullable();
            $table->string('country')->nullable();
            $table->integer('by')->nullable();

            // residential
            $table->integer('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('subdivision')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code')->nullable();

            $table->integer('same_addresses')->nullable();

            // permanent
            $table->integer('p_house_number')->nullable();
            $table->string('p_street')->nullable();
            $table->string('p_subdivision')->nullable();
            $table->string('p_barangay')->nullable();
            $table->string('p_city')->nullable();
            $table->string('p_province')->nullable();
            $table->string('p_zip_code')->nullable();

            // contact information
            $table->string('area_code')->nullable();
            $table->string('telephone_number')->nullable();
            $table->longtext('mobile_number')->nullable();
            $table->string('email_address')->nullable();

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
        Schema::dropIfExists('personal_information');
    }
}
