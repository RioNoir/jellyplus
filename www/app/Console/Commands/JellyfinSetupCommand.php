<?php

namespace App\Console\Commands;

use App\Services\Jellyfin\JellyfinApiManager;
use Illuminate\Console\Command;

class JellyfinSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jellyfin:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to setup Jellyfin for Jellyplus';

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
        dd('deprecated.');
        $this->info('start.');

        $start = microtime(true);
        set_time_limit(3600);
        ini_set('default_socket_timeout', 10);
        ini_set('memory_limit', '4000M');

        $api = new JellyfinApiManager();

        $this->info('####### Update Configuration #######');
        $api->updateConfiguration([
            'ServerName' => "Jellyplus #".crc32(env('HOSTNAME')),
            //'PreferredMetadataLanguage' => env('LANG', 'en'),
            //'MetadataCountryCode' => strtoupper(env('LANG', 'en')),
            //'UICulture' => strtolower(env('LANG', 'en')),
        ]);
        $api->updateBranding([
            'CustomCss' => "",
            'LoginDisclaimer' => "Welcome to Jellyplus! This service is open source, we take no responsibility for the use of this software, all misuse is at the user's discretion.",
            //'SplashscreenEnabled' => "'true'"
        ]);

        $this->info('####### Create library folders #######');

        foreach (jp_config('jellyfin.virtual_folders') as $virtualFolder){
            if(!file_exists($virtualFolder['path']))
                mkdir($virtualFolder['path'], 0777, true);

            system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".$virtualFolder['path']);
            $api->createVirtualFolderIfNotExist($virtualFolder['name'], $virtualFolder['path'], $virtualFolder['type']);
        }

        system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".jp_data_path('/jellyfin'), $result);
        system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".jp_data_path('/library'), $result);

        //$api->startLibraryScan();
        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");

        exit(1);
    }
}
