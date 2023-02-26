<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEditHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edit_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id');
            $table->integer('department_id');
            $table->string('tab');
            $table->string('catgory');
            $table->string('information_type');
            $table->longText('old');
            $table->longText('new');
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
        Schema::dropIfExists('edit_histories');
    }
}
