<?php

namespace App\Console\Commands;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class RemoveItemCommand extends Command
{
    protected $signature = 'library:remove-item {--itemId=}';
    protected $description = 'Command to remove Items to Jellyplus Library';
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('start.');
        Log::info('['.$this->getName().'] Command execution');
        //task_start($this->getName(), Carbon::now());

        $start = microtime(true);
        set_time_limit(3600);
        ini_set('default_socket_timeout', 10);
        ini_set('memory_limit', '4000M');

        $itemId = $this->option('itemId');
        try {
            if(!isset($itemId))
                throw new \Exception('Item id is required.');

            $item = Items::where('item_md5', $itemId)->first();
            if(!isset($item))
                throw new \Exception('Item not found on database.');

            $item->removeFromLibrary(true);

            $api = new JellyfinApiManager();
            $api->setAuthenticationByApiKey();
            $api->startLibraryScan();

            //Cache::flush();

            $this->info("end. (" . number_format(microtime(true) - $start, 2) . "s)\n");
            Log::info('[' . $this->getName() . '] Command execution ended');
            //task_end($this->getName(), Carbon::now());

            return Command::SUCCESS;
        }catch (\Exception $e){

            $this->info("failed. (" . number_format(microtime(true) - $start, 2) . "s)\n");
            Log::error('[' . $this->getName() . '] Command execution failed (Error: ' . $e->getMessage() . ')');
            //task_end($this->getName(), Carbon::now());

        }

        //exit(1);
        return Command::FAILURE;
    }
}
