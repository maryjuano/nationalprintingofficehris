<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmployeeSectionColumnToSectionId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employment_and_compensation', function (Blueprint $table) {
            $table->renameColumn('section', 'section_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employment_and_compensation', function (Blueprint $table) {
            $table->renameColumn('section_id', 'section');
        });
    }
}
