<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixTimeOffColor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_offs', function (Blueprint $table) {
            $table->bigInteger('time_off_color_id');
        });
        $data = array(
            array('color_name' => 'Orange Fun', 'color_hex' => '#FFCB80'),
            array('color_name' => 'Koko Caramel', 'color_hex' => '#E2A970'),
            array('color_name' => 'Pale Wood', 'color_hex' => '#F1D4B7'),
            array('color_name' => 'Sunny', 'color_hex' => '#FFEC6E'),
            array('color_name' => 'Cool Blues', 'color_hex' => '#70CCF9'),
            array('color_name' => 'Dark Ocean', 'color_hex' => '#5AA0D5'),
            array('color_name' => 'Forest', 'color_hex' => '#D2D561'),
            array('color_name' => 'Sea Blizz', 'color_hex' => '#BAE8FF'),
            array('color_name' => 'Blue Skies', 'color_hex' => '#82D6FF'),
            array('color_name' => 'Citrus Peel', 'color_hex' => '#FFEFA1'),
            array('color_name' => 'Piggy Pink', 'color_hex' => '#FDDDE6'),
        );
        DB::table('time_off_color')->insert($data);
        $time_offs = \App\TimeOff::all();
        foreach ($time_offs as $time_off) {
            $time_off_color = DB::table('time_off_color')->where('color_name', $time_off->color)->first();
            $time_off->time_off_color_id = $time_off_color->id;
            $time_off->save();
        }
        Schema::table('time_offs', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_offs', function (Blueprint $table) {
            $table->text('color');
            $table->dropColumn('time_off_color_id');
        });
    }
}
