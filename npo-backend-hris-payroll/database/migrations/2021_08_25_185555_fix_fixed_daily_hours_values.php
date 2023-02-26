<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FixFixedDailyHoursValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $times = \App\FixedDailyTimes::all();
        foreach ($times as $time) {
            $start_times = $time->start_times;
            foreach ($start_times as $key => $value) {
                $value = str_split($value);
                if (sizeof($value) == 8) {
                    $value[6] = '0';
                    $value[7] = '0';
                    $value = implode($value);
                    $start_times[$key] = $value;
                    // Log::debug($key . ":" . $value);
                }
            }
            $time->start_times = $start_times;

            $end_times = $time->end_times;
            foreach ($end_times as $key => $value) {
                $value = str_split($value);
                if (sizeof($value) == 8) {
                    $value[6] = '0';
                    $value[7] = '0';
                    $value = implode($value);
                    $end_times[$key] = $value;
                    // Log::debug($key . ":" . $value);
                }
            }
            $time->end_times = $end_times;
            $time->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
