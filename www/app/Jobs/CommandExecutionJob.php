<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CommandExecutionJob extends Job
{
    public $tries = 5;
    public $timeout = 3600;

    public $command, $arguments;
    public function __construct(string $command, array $arguments = [])
    {
        $this->command = $command;
        $this->arguments = $arguments;
    }

    public function attempts()
    {
        return 1;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('[' . $this->command . '] Job execution');
        Artisan::call($this->command, $this->arguments);

        if (Artisan::output()) {
            Log::info('[' . $this->command . '] Job output: "' . Artisan::output() . '"');
        }
    }

    public function failed(\Exception $e)
    {
        Log::error('[' . $this->command . '] Job execution failed (Error: ' . $e->getMessage() . ')');
    }
}
