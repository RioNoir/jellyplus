<?php

namespace App\Console\Commands;


use App\Services\Helpers\FileHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use function Symfony\Component\String\s;

class IMDbDatasetRetriever extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imdb:dataset-retriever';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve data from IMDb';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('default_socket_timeout', -1);
        ini_set('memory_limit', '-1');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        dd('deprecated.');

        $this->info('start.');

        $start = microtime(true);
        try {
            //Titles import
            $this->info('######### Titles Import ##########');
            $titles = static::getTitles();
            //Episodes
            $this->info('######### Episodes Import ##########');
            $episodes = static::getEpisodes();
        }catch (\Exception $e){
            dd($e);
        }

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");
    }

    /*
     * Non-commercial Dataset from Imdb
     * Guide https://developer.imdb.com/non-commercial-datasets/#titleepisodetsvgz
     */
    private static $datasetUrl = "https://datasets.imdbws.com";
    private static $datasetStoragePath = "app/imdb/datasets";


    private static function getTitles()
    {
        $path = jp_data_path('app/imdb/titles.json');
        if(!file_exists($path)){
            $header = [];
            $items = [];
            $lines = static::getDatasetFiles("title.basics.tsv.gz");
            foreach ($lines as $key => $line) {
                if ($key == 0) {
                    $header = explode("\t", $line);
                    continue;
                }
                $item = explode("\t", $line);
                if (count($header) !== count($item))
                    continue;

                $item = array_combine($header, $item);

                //Filters
                if (!in_array($item['titleType'], ['movie', 'tvseries', 'tvepisode']))
                    continue;

                if ((int)$item['startYear'] < 2000)
                    continue;

                $items[] = $item;
                echo "- import count: ".count($items)."\n";
            }
            file_put_contents($path, json_encode($items));
        }
        return $path;
    }

    private static function getEpisodes()
    {
        $path = jp_data_path('app/imdb/episodes.json');
        if(!file_exists($path)) {
            $header = [];
            $items = [];
            $lines = static::getDatasetFiles("title.episode.tsv.gz");
            foreach ($lines as $key => $line) {
                if ($key == 0) {
                    $header = explode("\t", $line);
                    continue;
                }
                $item = explode("\t", $line);
                if (count($header) !== count($item))
                    continue;

                $item = array_combine($header, $item);
                $items[] = $item;
                echo "- import count: ".count($items)."\n";
            }
            file_put_contents($path, json_encode($items));
        }
        return $path;
    }

    private static function getDatasetFiles($fileName)
    {
        $path = jp_data_path(static::$datasetStoragePath);
        if(!file_exists($path))
            mkdir($path, 0777, true);

        $filePath = $path."/".$fileName;
        $fileInfo = pathinfo($filePath);
        if(!file_exists($filePath) || (file_exists($filePath) && Carbon::parse(filemtime($filePath))->addHour()->isBefore(Carbon::now()->subHour()))){
            $url = self::$datasetUrl."/".$fileName;
            file_put_contents($filePath, fopen($url, 'r'));
        }

        $uncopressedFolder = $fileInfo['dirname']."/uncompressed";
        if(!file_exists($uncopressedFolder))
            mkdir($uncopressedFolder);

        $uncopressedFilePath = $uncopressedFolder."/".str_replace('.gz', '', $fileName);
        if(!file_exists($uncopressedFilePath) || (file_exists($uncopressedFilePath) && Carbon::parse(filemtime($uncopressedFilePath))->addHour()->isBefore(Carbon::now()->subHour()))){
            $uncopressedFilePath = FileHelper::extractGZ($filePath, $uncopressedFilePath);
        }

        return file($uncopressedFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        //return FileHelper::splitFile($uncopressedFilePath, '51200k'); //50mb
    }

}
