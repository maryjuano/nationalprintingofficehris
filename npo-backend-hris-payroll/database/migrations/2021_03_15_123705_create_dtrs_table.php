<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDtrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dtrs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('dtr_submit_id');
            $table->bigInteger('employee_id');
            $table->date('dtr_date');
            $table->longText('in')->nullable();
            $table->longText('out')->nullable();
            $table->longText('break_start')->nullable();
            $table->longText('break_end')->nullable();
            $table->longText('holiday')->nullable();
            $table->longText('overtime_request')->nullable();
            $table->longText('time_off_request')->nullable();
            $table->boolean('is_restday')->default(false);
            $table->bigInteger('rendered_minutes')->default(0);
            $table->bigInteger('overtime_minutes')->default(0);
            $table->bigInteger('late_minutes')->default(0);
            $table->bigInteger('undertime_minutes')->default(0);
            $table->bigInteger('night_differential_minutes')->default(0);
            $table->integer('absence')->default(0);
            $table->longText('overtime')->nullable();
            $table->decimal('late_for_payment_deduction', 11, 3)->default(0.00);
            $table->integer('absence_for_payment_deduction')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtrs');
    }
}
