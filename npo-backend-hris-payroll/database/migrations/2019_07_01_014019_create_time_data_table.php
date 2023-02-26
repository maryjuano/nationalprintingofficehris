<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_data', function (Blueprint $table) {
            $table->string('time_data_name');
            $table->float('multiplier');
            $table->boolean('status')->default(true);
            $table->bigIncrements('id');
            $table->string('created_by');
            $table->dateTime('created_at');
            $table->string('updated_by');
            $table->dateTime('updated_at');
        });

        $data = array(
            [
                "time_data_name" => "Night Differential",
                "multiplier" => 0.10,
                "status" => 1,
                "created_by" => 1,
                "updated_by" => 1,
                // "multiplierRest" => 0.00,
                // "multiplierOT" => 0.00,
                // "multiplierNightDiff" => 0.00,
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now()
            ],
            [
                "time_data_name" => "Rest",
                "multiplier" => 0.13,
                "status" => 1,
                "created_by" => 1,
                "updated_by" => 1,
                // "multiplierRest" => 0.00,
                // "multiplierOT" => 0.00,
                // "multiplierNightDiff" => 0.00,
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now()
            ],
            [
                "time_data_name" => "Regular",
                "multiplier" =>  537,
                "status" => 1,
                "created_by" => 1,
                "updated_by" => 1,
                // "multiplierRest" => 0.00,
                // "multiplierOT" => 0.00,
                // "multiplierNightDiff" => 0.00,
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now()
            ]
        );

        \DB::table('time_data')->insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_data');
    }
}
