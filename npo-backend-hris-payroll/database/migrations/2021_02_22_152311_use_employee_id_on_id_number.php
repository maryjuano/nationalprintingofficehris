<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UseEmployeeIdOnIdNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $employee_id_numbers = \App\EmployeeIdNumber::all();
        foreach ($employee_id_numbers as $employee_id_number) {
            $employee = \App\Employee::where('users_id', $employee_id_number->users_id)->first();
            if ($employee) {
                $employee_id_number->users_id = $employee->id;
                $employee_id_number->save();
            }
        }
        Schema::table('employee_id_number', function (Blueprint $table) {
            $table->renameColumn('users_id', 'employee_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_id_number', function (Blueprint $table) {
            $table->renameColumn('employee_id', 'users_id');
        });
    }
}
