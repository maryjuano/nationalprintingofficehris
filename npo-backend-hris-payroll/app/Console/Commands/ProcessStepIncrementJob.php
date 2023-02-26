<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessStepIncrementJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:process_step_increment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes all employees and ajusts their step increment if applicable';

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
        $this->info("Starting ProcessStepIncrement()");
        dispatch(new \App\Jobs\ProcessStepIncrement());
    }
}
