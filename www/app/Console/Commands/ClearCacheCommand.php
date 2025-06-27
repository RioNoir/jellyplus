<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to clear Jellyplus cache';

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
     * @return mixed
     */
    public function handle()
    {
        $this->info('start.');
        Log::info('['.$this->getName().'] Command execution');
        task_start($this->getName(), Carbon::now());

        $start = microtime(true);
        set_time_limit(3600);
        ini_set('default_socket_timeout', 10);
        ini_set('memory_limit', '4000M');

        Cache::flush();
        //Session::flush();

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");
        Log::info('['.$this->getName().'] Command execution ended');
        task_end($this->getName(), Carbon::now());

        //exit(1);
        return Command::SUCCESS;
    }
}
