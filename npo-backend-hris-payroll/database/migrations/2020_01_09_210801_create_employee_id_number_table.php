<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateEmployeeIdNumberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_id_number', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('users_id');
            $table->string('code');
            $table->integer('year');
            $table->integer('month');
            $table->integer('day');
            $table->string('date');
            $table->string('id_number');
            $table->integer('number');
            $table->timestamps();
        });

        DB::table('employee_id_number')->insert([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'users_id' => 0,
            'code' => 'NPO',
            'date' => '0000/00/00',
            'year' => 00,
            'month' => 00,
            'day' => 00,
            'number' => 000,
            'id_number' => "NPO00000000 ",

        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_id_number');
    }
}
