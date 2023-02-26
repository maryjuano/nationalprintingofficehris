<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PullJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pulljobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \App\Jobs\PullBiometrics::dispatchSync('192.168.0.245');
        \App\Jobs\PullBiometrics::dispatchSync('192.168.0.246');
        \App\Jobs\PullBiometrics::dispatchSync('192.168.0.247');
        \App\Jobs\PullBiometrics::dispatchSync('192.168.0.248');
        \App\Jobs\PullBiometrics::dispatchSync('192.168.0.249');
    }
}
