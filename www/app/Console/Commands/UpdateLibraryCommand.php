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

class UpdateLibraryCommand extends Command
{
    protected $signature = 'library:update';
    protected $description = 'Command to update Jellyplus Library';

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
            //Cache::flush();
            //Session::flush();
            Log::info('[' . $this->getName() . '] cache cleared.');
            $this->info('[' . $this->getName() . '] cache cleared.');

            //Faccio l'aggiornamento delle serie tv per vedere se ci sono nuovi episodi
            $items = Items::query()->where('item_type', 'tvSeries')->whereNotNull('item_jellyfin_id')->whereNotNull('item_path')
                ->where('updated_at', '<=', Carbon::now()->subHours(jp_config('jellyfin.update_series_after')))->get();
            $count = $items->count();
            foreach ($items as $item) {
                $titleData = $item->getTitleData();
                if (!isset($titleData['enddate']) && !in_array(strtolower($titleData['status']), ['ended', 'finished'])) { //Solo se non è conclusa
                    $item->updateItemToLibrary();
                    sleep(3);
                }
            }
            Log::info('[' . $this->getName() . '] ' . $count . ' tv series updated.');
            $this->info('[' . $this->getName() . '] ' . $count . ' tv series updated.');

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

        //exit(1);
        return Command::FAILURE;
    }
}
