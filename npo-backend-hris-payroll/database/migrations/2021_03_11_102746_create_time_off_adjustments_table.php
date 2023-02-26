<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CreateTimeOffAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_off_adjustments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('time_off_balance_id');
            $table->decimal('adjustment_value', 8, 3);
            $table->date('effectivity_date');
            $table->longText('remarks')->nullable();
        });
        $time_off_balances = \App\TimeOffBalance::all();
        foreach ($time_off_balances as $time_off_balance) {
            \App\TimeOffAdjustment::create([
                'time_off_balance_id' => $time_off_balance->id,
                'adjustment_value' => $time_off_balance->points,
                'effectivity_date' => Carbon::now()->toDateString(),
                'remarks' => 'Initialized value'
            ]);
        }
        Schema::table('time_off_balance', function (Blueprint $table) {
            $table->dropColumn('number_of_days');
            $table->dropColumn('points');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('time_off_balance', function (Blueprint $table) {
            $table->float('number_of_days', 6, 3);
            $table->float('points', 6, 3);
        });
        $time_off_adjustments = \App\TimeOffAdjustment::all();
        foreach ($time_off_adjustments as $time_off_adjustment) {
            $time_off_balance = \App\TimeOffBalance::find($time_off_adjustment->time_off_balance_id);
            if ($time_off_balance) {
                $time_off_balance->points += $time_off_adjustment->adjustment_value;
                $time_off_balance->number_of_days += $time_off_adjustment->adjustment_value;
                $time_off_balance->save();
            }
        }
        Schema::dropIfExists('time_off_adjustments');
    }
}
