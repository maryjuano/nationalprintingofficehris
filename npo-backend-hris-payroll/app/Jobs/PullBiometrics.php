<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Log;

use Local\Zklib\ZKLib;

class PullBiometrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $zk = new ZKLib($this->ip);
        Log::info("Pulling user information from {$this->ip}");
        $zk->fetchUsers();
        Log::info("Biometrics user logs have been fetched!");

        Log::info("Pulling biometrics from {$this->ip}");
        $zk->fetchAttendanceLogs();
        Log::info("Biometrics attendance logs have been fetched!");

        $zk->clearAttendanceLogs();
    }
}
