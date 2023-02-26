<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateGsisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gsis', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->float('personal_share');
            $table->float('government_share');
            $table->integer('ecc');
            $table->boolean('status')->default(0);
        });

        DB::table('gsis')->insert([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'personal_share' => 0.09,
            'government_share' => 0.12,
            'ecc' => 100
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gsis');
    }
}
