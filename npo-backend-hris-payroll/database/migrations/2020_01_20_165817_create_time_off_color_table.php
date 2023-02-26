<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeOffColorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_off_color', function (Blueprint $table) {
            $table->increments('id');
            $table->string('color_name');
            $table->string('color_hex');
            $table->timestamps();
        });

        $data = array(
            array('color_name' => 'Greeny', 'color_hex' => '#F8FFAE'),
            array('color_name' => 'Honey Dew', 'color_hex' => '#43C6AC'),
            array('color_name' => 'Reaqua', 'color_hex' => '#ACBB78'),
            array('color_name' => 'Deep blue', 'color_hex' => '#108dc7'),
            array('color_name' => 'Orange Coral', 'color_hex' => '#ff9966'),

            array('color_name' => 'Light orange', 'color_hex' => '#feb47b'),
            array('color_name' => 'Grass', 'color_hex' => '#BFE6BA'),
            array('color_name' => 'Cool Brown', 'color_hex' => '#b29f94'),
            array('color_name' => 'Jupiter', 'color_hex' => '#ffd89b'),
            array('color_name' => 'Dark Forest', 'color_hex' => '#2C7744'),

            array('color_name' => 'Sublime Light', 'color_hex' => '#C6FFDD'),
            array('color_name' => 'Hard Rose', 'color_hex' => '#D5435C'),
            array('color_name' => 'Light Purple', 'color_hex' => '#EAAFC8'),
            array('color_name' => 'Dark Green', 'color_hex' => '#288A76'),
            array('color_name' => 'Sunny Flower', 'color_hex' => '#FFF347'),

            array('color_name' => 'Dirty Sun', 'color_hex' => '#CFC640'),
            array('color_name' => 'Shifty', 'color_hex' => '#DED240'),
            array('color_name' => 'Greeny Moss', 'color_hex' => '#68620E'),
            array('color_name' => 'Cool Soil', 'color_hex' => '#87823B'),
            array('color_name' => 'Little Leaf', 'color_hex' => '#8DC26F'),

            array('color_name' => 'Branch', 'color_hex' => '#B85411'),
            array('color_name' => 'Tree Bark', 'color_hex' => '#D5844D'),
            array('color_name' => 'Rocky Brown', 'color_hex' => '#954E1E'),
            array('color_name' => 'Deep Space', 'color_hex' => '#456D65'),
            array('color_name' => 'Royal', 'color_hex' => '#A2269C'),

            array('color_name' => 'Semi Royal', 'color_hex' => '#985DCC'),
            array('color_name' => 'Hydrogen', 'color_hex' => '#206592'),
            array('color_name' => 'Neuromancer', 'color_hex' => '#E673B4'),
            array('color_name' => 'Minted', 'color_hex' => '#57E197'),
            array('color_name' => 'Mojito', 'color_hex' => '#3CC532'),

        );

        DB::table('time_off_color')->insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_off_color');
    }
}
