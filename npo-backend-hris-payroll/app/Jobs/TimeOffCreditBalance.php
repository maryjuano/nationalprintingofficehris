<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Log;
use Carbon\Carbon;

class TimeOffCreditBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Starting TimeOffCreditBalance Job...");
        
        $time_off_balances = \App\TimeOffBalance::query()
        ->leftJoin('time_offs', 'time_offs.id', 'time_off_balance.time_off_id')
        ->whereRaw("DATE_FORMAT(time_offs.monthly_credit_date, '%m-%d') = (?)", [Carbon::now()->format('m-d')])
        ->get();

        foreach ($time_off_balances as $time_off_balance) {
            \App\TimeOffAdjustment::create([
                'time_off_balance_id' => $time_off_balance->id,
                'adjustment_value' => $time_off_balance->monthly_credit_balance,
                'effectivity_date' => Carbon::now()->toDateString(),
                'remarks' => 'Monthly balance credit'
            ]);
        }

        Log::info("Ending TimeOffCreditBalance Job...");
    }
}
