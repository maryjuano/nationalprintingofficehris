<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new \App\Jobs\PullBiometrics('192.168.0.245'))->everyThirtyMinutes(); //->dailyAt('01:00');
        $schedule->job(new \App\Jobs\PullBiometrics('192.168.0.246'))->everyThirtyMinutes(); //->dailyAt('01:00');
        $schedule->job(new \App\Jobs\PullBiometrics('192.168.0.247'))->everyThirtyMinutes(); //->dailyAt('01:00');
        $schedule->job(new \App\Jobs\PullBiometrics('192.168.0.248'))->everyThirtyMinutes(); //->dailyAt('01:00');
        $schedule->job(new \App\Jobs\PullBiometrics('192.168.0.249'))->everyThirtyMinutes(); //->dailyAt('01:00');

        $schedule->job(new \App\Jobs\IngestBiometrics())->everyTenMinutes();//->daily('03:00');
        $schedule->job(new \App\Jobs\ProcessStepIncrement())->monthlyOn(1, '2:00');
        $schedule->job(new \App\Jobs\TimeOffCreditBalance())->daily('00:30');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
