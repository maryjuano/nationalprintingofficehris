<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortToDepartments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->integer('sort')->default(100);

        });
        $item = \App\Department::find(1);
        $item->sort = 1;
        $item->save();
        $item = \App\Department::find(2);
        $item->sort = 2;
        $item->save();
        $item = \App\Department::find(3);
        $item->sort = 6;
        $item->save();
        $item = \App\Department::find(4);
        $item->sort = 3;
        $item->save();
        $item = \App\Department::find(5);
        $item->sort = 7;
        $item->save();
        $item = \App\Department::find(6);
        $item->sort = 8;
        $item->save();
        $item = \App\Department::find(7);
        $item->sort = 9;
        $item->save();
        $item = \App\Department::find(8);
        $item->sort = 4;
        $item->save();
        $item = \App\Department::find(9);
        $item->sort = 5;
        $item->save();
        $item = \App\Department::find(10);
        $item->sort = 10;
        $item->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('sort');
        });
    }
}
