<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPercentageToPhilhealth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('philhealth', function (Blueprint $table) {
            $table->dropColumn('is_percentage');
            $table->boolean('is_max');
            $table->float('percentage');
            $table->boolean('is_percentage')->change();
            $table->decimal('minimum_range', 12, 2)->change();
            $table->decimal('maximum_range', 12, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('philhealth', function (Blueprint $table) {
            $table->dropColumn('percentage');
            $table->dropColumn('is_max');
            $table->boolean('is_percentage');
            $table->float('minimum_range')->change();
            $table->float('maximum_range')->change();
        });
    }
}
