<?php

namespace App\Console\Commands;


use App\Services\IMDB\IMDBApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Items\ItemsSearchManager;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test command';

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

        $start = microtime(true);
        set_time_limit(3600);
        ini_set('default_socket_timeout', 10);
        ini_set('memory_limit', '4000M');

        dd('just for tests.');

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");
    }
}
