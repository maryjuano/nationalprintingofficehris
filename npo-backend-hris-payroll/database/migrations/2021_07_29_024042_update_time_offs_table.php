<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class UpdateTimeOffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_offs', function (Blueprint $table) {
            $table->decimal('monthly_credit_balance')->default(0);
            $table->integer('monthly_credit_date')->nullable();
            $table->string('unit')->default('day');
            $table->renameColumn('balance_credit', 'default_balance');
            $table->date('annual_credit_reset');
        });

        $time_offs = \App\TimeOff::all();
        foreach($time_offs as $time_off) {
            if ($time_off->carry_over_type === 0) {
                $time_off->monthly_credit_balance = $time_off->default_balance;
                $time_off->monthly_credit_date = $time_off->day;
                $time_off->default_balance = 0;
            }
            $date_now = Carbon::now();
            $date_now->month = $time_off->month ?? 1;
            $date_now->day = $time_off->day;
            $time_off->annual_credit_reset = $date_now->format('Y-m-d');
            $time_off->unit = $time_off->balance_credit_type === 1 ? 'hour' : 'day';
            $time_off->save();
        }

        Schema::table('time_offs', function (Blueprint $table) {
            $table->dropColumn('balance_credit_type');
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
            $table->dropColumn('monthly_credit_balance');
            $table->dropColumn('monthly_credit_date');
            $table->renameColumn('default_balance', 'balance_credit');
            $table->integer('balance_credit_type')->default(0);
        });
    }
}
