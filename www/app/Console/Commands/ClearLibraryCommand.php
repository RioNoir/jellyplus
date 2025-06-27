<?php

namespace App\Console\Commands;

use App\Services\Jellyfin\JellyfinApiManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearLibraryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to clear Jellyplus Library';

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

        try {
            //Artisan::call('cache:clear');
            //Cache::flush();
            Artisan::call('migrate:fresh');

            //Remove library folder
            system("rm -rf " . escapeshellarg(jp_data_path('library')));

            //Re-create library structure
            foreach (jp_config('jellyfin.virtual_folders') as $virtualFolder) {
                if (!file_exists($virtualFolder['path']))
                    mkdir($virtualFolder['path'], 0777, true);

                //Set permissions
                system("chown -R " . env('USER_NAME') . ":" . env('USER_NAME') . " " . $virtualFolder['path']);
            }

            $api = new JellyfinApiManager();
            $api->setAuthenticationByApiKey();
            $api->startLibraryScan();

            $this->info("end. (" . number_format(microtime(true) - $start, 2) . "s)\n");
            Log::info('[' . $this->getName() . '] Command execution ended');
            task_end($this->getName(), Carbon::now());

            return Command::SUCCESS;
        }catch (\Exception $e){

            $this->info("failed. (" . number_format(microtime(true) - $start, 2) . "s)\n");
            Log::error('[' . $this->getName() . '] Command execution failed (Error: ' . $e->getMessage() . ')');
            task_end($this->getName(), Carbon::now());

        }

        //exit(1);
        return Command::FAILURE;
    }
}
