<?php

namespace App\Console\Commands;

use App\Models\Items;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RebuildLibraryCommand extends Command
{
    protected $signature = 'library:rebuild';
    protected $description = 'Command to rebuild folder structure of Jellyplus Library';
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
            $virtualFolders = jp_config('jellyfin.virtual_folders');
            foreach ($virtualFolders as $folder) {
                Log::info('[' . $this->getName() . '] building ' . $folder['type'] . ' folder.');
                $this->info('[' . $this->getName() . '] building ' . $folder['type'] . ' folder.');

                $tree = dir_tree($folder['path'], true);
                foreach ($tree as $itemPath => $files) {
                    $path = str_replace(jp_data_path(''), '', $itemPath);
                    $item = Items::findByPath($path);
                    if(isset($item))
                        $item->updateItemToLibrary();
                }
            }

            Log::info('[' . $this->getName() . '] start library scan.');
            $this->info('[' . $this->getName() . '] start library scan.');

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
