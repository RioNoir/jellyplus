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

class DownloadStreamCommand extends Command
{
    protected $signature = 'library:download-stream {--url=} {--path=} {--filename=}';
    protected $description = 'Command to file from Stream Url';

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
        ini_set('memory_limit', -1);

        $url = $this->option('url');
        $path = $this->option('path');
        $filename = $this->option('filename');
        try {
            Log::info('[' . $this->getName() . '] ' .' downloading started ('.$url.').');
            $this->info('[' . $this->getName() . '] ' .' downloading started ('.$url.').');

            $filePath = download_file_from_url($url, $path);
            if($filePath){
                $fileInfo = pathinfo($filePath);
                $newFileName = $filename . ' - ' .$fileInfo['filename'];
                $newFilePath = str_replace($fileInfo['filename'], $newFileName, $filePath);

                if(file_exists($newFilePath)){
                    $i = 2;
                    while (!file_exists($newFilePath)) {
                        $newFilePath = str_replace($fileInfo['filename'], $newFileName.'-'.$i, $filePath);
                        $i++;
                    }
                }

                if(rename($filePath, $newFilePath)){
                    Log::info('[' . $this->getName() . '] ' .' download successfully ('.$newFilePath.').');
                    $this->info('[' . $this->getName() . '] ' .' download successfully ('.$newFilePath.').');
                }else{
                    Log::error('[' . $this->getName() . '] ' .' error while renaming file ('.$filePath.').');
                    $this->error('[' . $this->getName() . '] ' .' error while renaming file ('.$filePath.').');
                }
            }else{
                Log::error('[' . $this->getName() . '] ' .' error while downloading from ('.$url.').');
                $this->error('[' . $this->getName() . '] ' .' error while downloading from ('.$url.').');
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
