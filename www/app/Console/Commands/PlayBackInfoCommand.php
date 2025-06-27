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

class PlayBackInfoCommand extends Command
{
    protected $signature = 'library:playback-info {--itemId=}';
    protected $description = 'Command to get PlayBack Info of Jellyplus Library';
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

        $itemId = $this->option('itemId');
        try {
            //Cache::flush();
            Log::info('[' . $this->getName() . '] cache cleared.');

            $count = 0;

            $api = new JellyfinApiManager();
            if ($api->testApiKey()) {
                $api->setAuthenticationByApiKey();
                $adminUsers = $api->getUsers();
                $adminUser = collect($adminUsers)->where('Policy.IsAdministrator', '=', true)->first();

                $playBackItems = [];
                $virtualFolders = $api->getVirtualFolders();
                if (isset($virtualFolders)) {
                    foreach ($virtualFolders as $folder) {
                        $vFolder = collect(jp_config('jellyfin.virtual_folders'))
                            ->whereIn('path', array_values($folder['Locations']))->first();
                        if (isset($vFolder)) {
                            $itemsTypesMap = ['tvshows' => 'Series', 'movies' => 'Movie'];
                            $items = $api->getUsersItems($adminUser['Id'], [
                                'ParentId' => $folder['ItemId'],
                                'IncludeItemTypes' => $itemsTypesMap[$vFolder['type']],
                                'filters' => 'IsUnplayed'
                            ]);

                            if (!empty(@$items['Items'])) {
                                foreach ($items['Items'] as $item) {
                                    $item = $api->getItem(@$item['Id'], ['userId' => $adminUser['Id']]);

                                    if(isset($itemId) && $item['Id'] !== $itemId)
                                        continue;

                                    if ($item['Type'] == "Series") {
                                        $seasons = $api->getSeasons($item['Id'], ['userId' => $adminUser['Id']]);
                                        if (!empty(@$seasons['Items'])) {
                                            foreach ($seasons['Items'] as $season) {
                                                $episodes = $api->getEpisodes($item['Id'], ['seasonId' => $season['Id'], 'userId' => $adminUser['Id']]);
                                                if (!empty(@$episodes['Items'])) {
                                                    foreach ($episodes['Items'] as $episode) {
                                                        $jItem = $api->getItem($episode['Id'], ['userId' => $adminUser['Id']]);
                                                        if (empty(@$jItem['MediaStreams']))
                                                            $playBackItems[$jItem['Id']] = $jItem;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if (empty(@$item['MediaStreams']))
                                            $playBackItems[$item['Id']] = $item;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($playBackItems)) {
                    $playBackItems = array_slice($playBackItems, 0, config('jellyfin.update_playback_limit'));
                    foreach ($playBackItems as $id => $playBackItem) {
                        $api->getItemPlaybackInfo($id);
                        $count++;
                    }
                }
            }
            Log::info('[' . $this->getName() . '] ' . $count . ' playback info retrieved.');


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
