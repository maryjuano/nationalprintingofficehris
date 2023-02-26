<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmployeeTypeTimeOffPivot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_type_time_off', function (Blueprint $table) {
            $table->foreignId('employee_type_id')->constrained('employee_types');
            $table->foreignId('time_off_id')->constrained('time_offs');
        });

        $time_offs = \App\TimeOff::all();
        foreach (\App\EmployeeType::all() as $employee_type) {
            foreach ($employee_type->time_offs_ids as $time_off_id) {
                \DB::table('employee_type_time_off')->insert([
                    'employee_type_id' => $employee_type->id,
                    'time_off_id' => $time_off_id
                ]);
            }
        }

        Schema::table('employee_types', function (Blueprint $table) {
            $table->dropColumn('time_offs_ids');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_type_time_off');
    }
}
