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

class CleanLibraryCommand extends Command
{
    protected $signature = 'library:clean';
    protected $description = 'Command to clean Jellyplus Library';

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
            Cache::flush();
            //Session::flush();
            Log::info('[' . $this->getName() . '] cache cleared.');
            $this->info('[' . $this->getName() . '] cache cleared.');

            //Elimino gli items che esistono da più di 5 giorni e non sono in libreria
            $items = Items::query()->whereNull('item_jellyfin_id')->whereNull('item_path')
                ->where('created_at', '<=', Carbon::now()->subDays(jp_config('jellyfin.delete_unused_after')))->get();
            $count = $items->count();
            foreach ($items as $item) {
                $item->delete();
            }
            Log::info('[' . $this->getName() . '] ' . $count . ' unused items removed.');
            $this->info('[' . $this->getName() . '] ' . $count . ' unused items removed.');

            //Elimino le stream create da più di tot ore
            Streams::query()->where('created_at', '<=', Carbon::now()->subHours(jp_config('jellyfin.delete_streams_after')))->delete();
            Log::info('[' . $this->getName() . '] old streams deleted.');
            $this->info('[' . $this->getName() . '] old streams deleted.');

            $api = new JellyfinApiManager();
            //Controllo se funziona ancora l'api key
            if ($api->testApiKey()) {
                $api->setAuthenticationByApiKey();
                $api->startLibraryScan();
            } else {
                jp_config('api_key', '');
            }

            $this->info("end. (" . number_format(microtime(true) - $start, 2) . "s)\n");
            Log::info('[' . $this->getName() . '] Command execution ended');
            task_end($this->getName(), Carbon::now());

            return Command::SUCCESS;
        }catch (\Exception $e){

            $this->info("failed. (" . number_format(microtime(true) - $start, 2) . "s)\n");
            Log::error('[' . $this->getName() . '] Command execution failed (Error: ' . $e->getMessage() . ')');
            task_end($this->getName(), Carbon::now());

        }

        return Command::FAILURE;
    }
}
