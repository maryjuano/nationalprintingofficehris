<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateSalaryRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_ranges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('max_grades')->default(0);
            $table->integer('max_steps')->default(0);
            $table->timestamps();
        });

        DB::table('salary_ranges')->insert([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'max_steps' => 0,
            'max_grades' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salary_ranges');
    }
}
