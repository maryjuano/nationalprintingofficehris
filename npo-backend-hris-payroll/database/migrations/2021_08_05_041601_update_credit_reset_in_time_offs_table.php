<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCreditResetInTimeOffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_offs', function (Blueprint $table) {
            $table->integer('annual_credit_reset_month');
            $table->integer('annual_credit_reset_day');
            $table->decimal('minimum_used_credits')->default(0);
            $table->renameColumn('carry_over', 'can_carry_over');
        });

        $time_offs = \App\TimeOff::all();
        foreach($time_offs as $time_off) {
            $time_off->annual_credit_reset_month = $time_off->month ?? 1;
            $time_off->annual_credit_reset_day = $time_off->day;
            $time_off->save();
        }
        
        Schema::table('time_offs', function (Blueprint $table) {
            $table->dropColumn('month');
            $table->dropColumn('day');
            $table->dropColumn('carry_over_type');
            $table->dropColumn('annual_credit_reset');
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
            $table->integer('month')->nullable();
            $table->integer('day')->nullable();
        });

        $time_offs = \App\TimeOff::all();
        foreach($time_offs as $time_off) {
            $time_off->month = $time_off->annual_credit_reset_month;
            $time_off->day = $time_off->annual_credit_reset_day;
            $time_off->save();
        }
        
        Schema::table('time_offs', function (Blueprint $table) {
            $table->dropColumn('annual_credit_reset_month');
            $table->dropColumn('annual_credit_reset_day');
            $table->dropColumn('minimum_used_credits');
            $table->renameColumn('can_carry_over', 'carry_over');
            $table->boolean('carry_over')->default(false);
            $table->integer('carry_over_type')->nullable();
            $table->date('annual_credit_reset');
        });
    }
}
