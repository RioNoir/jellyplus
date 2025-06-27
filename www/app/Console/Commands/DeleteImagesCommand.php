<?php

namespace App\Console\Commands;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Jellyfin\JellyfinApiManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class DeleteImagesCommand extends Command
{
    protected $signature = 'delete:images';
    protected $description = 'Command to delete Jellyplus images';

    public function __construct()
    {
        parent::__construct();
    }

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
            //Remove images folder
            system("rm -rf " . escapeshellarg(jp_data_path('app/images')));
            //Re-create images folder
            if (!file_exists(jp_data_path('app/images')))
                mkdir(jp_data_path('app/images'), 0777, true);
            //Set permissions
            system("chown -R " . env('USER_NAME') . ":" . env('USER_NAME') . " " . escapeshellarg(jp_data_path('app/images')));

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
