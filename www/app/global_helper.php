<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

if (!function_exists('_env')) {
    function _env($key, $default = null){
        if(!str_starts_with($key, 'JP_')) {
            $key = 'JP_' . $key;
        }
        return \Illuminate\Support\Env::get($key, $default);
    }
}

if (!function_exists('jp_data_path')) {
    function jp_data_path($path){
        if(str_starts_with($path, '/')) {
            $path = substr($path, 1);
        }
        return '/data/' . $path;
        //return realpath('/data/'.$path);
    }
}

if (!function_exists('jp_url')) {
    function jp_url($path = "", array $query = []){
        $url = jp_config('app.url');
        $path = !str_starts_with($path, '/') ? '/' . $path : $path;
        $path .= !empty($query) ? '?' . http_build_query($query, '', '&') : '';
        return $url . $path;
    }
}

if (!function_exists('jp_config')) {
    function jp_config(string $key = null, $value = null) {
        $args = func_get_args();
        try {
            if(!file_exists(config('app.config')))
                json_to_file(config('app.config'), []);

            if(file_exists(config('app.config'))) {
                $json = json_from_file(config('app.config'));
                if (array_key_exists(1, $args)) {
                    $currentValue = data_get($json, $key);
                    $cvMd5 = md5(is_array($currentValue) ? json_encode($currentValue) : $currentValue);
                    $nvMd5 = md5(is_array($value) ? json_encode($value) : $value);
                    if($cvMd5 !== $nvMd5){
                        data_set($json, $key, $value);
                        if (!empty($json)) {
                            Log::info("[config][".get_client_ip()."] saving new config: \n".json_encode($json));
                            json_to_file(config('app.config'), $json);
                        }
                    }
                }
                $value = $json;
                if(isset($key))
                    $value = data_get($json, $key);
            }

            $config = config($key, null);
            if ($value === null)
                $value = $config;

            return $value;
        }catch (\Exception $e){
            Log::error($e);
        }
        return null;
    }
}

if (!function_exists('t')) {
    function t(string $string){
        $t = 'jellyfin.'.$string;

        if(trans($t) !== $t)
            return trans($t);

        return $string;
    }
}

if (!function_exists('jpt')) {
    function jpt(string $string){
        $t = 'jellyplus.'.$string;

        if(trans($t) !== $t)
            return trans($t);

        return $string;
    }
}

if(!function_exists('ping')) {
    function ping($url) :bool {
        try {
            $url = parse_url($url);
            $end = "\r\n\r\n";
            $waitTimeoutInSeconds = 5;
            $fp = fsockopen($url['host'], (empty($url['port']) ? 80 : $url['port']), $errno, $errstr, $waitTimeoutInSeconds);
            if ($fp) {
                $out = "GET / HTTP/1.1\r\n";
                $out .= "Host: " . $url['host'] . "\r\n";
                $out .= "Connection: Close\r\n\r\n";
                $var = '';
                fwrite($fp, $out);
                while (!feof($fp)) {
                    $var .= fgets($fp, 1280);
                    if (strpos($var, $end))
                        break;
                }
                fclose($fp);
                $var = preg_replace("/\r\n\r\n.*\$/", '', $var);
                $var = explode("\r\n", $var);
                if (isset($var[0])) {
                    $httpCode = explode(' ', $var[0]);
                    if (isset($httpCode[1]) && (int) $httpCode[1] >= 200 && (int) $httpCode[1] < 400) {
                        return true;
                    }
                }
            }
        }catch (\Exception $e){}
        return false;
    }
}

if(!function_exists('test_url')) {
    function test_url($url)
    {
        try {
            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($handle, CURLOPT_TIMEOUT, 1);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($handle);
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if ($httpCode >= 200 && $httpCode < 300)
                return true;
        } catch (\Exception $e) {
        }
        return false;
    }
}

if (!function_exists('app_url')) {
    function app_url($path = "", array $query = []){
        $url = jp_config('url');

        if(!empty(jp_config('external_url')))
            $url = jp_config('external_url');

        if(!ping($url)){
            $uInfo = parse_url($url);
            if (isset($uInfo['scheme'], $uInfo['host']))
                $url = $uInfo['scheme'] . '://' . $uInfo['host'];
        }

        if(empty($url)) {
            $protocol = "http";
            if(env('HTTP_X_FORWARDED_PROTO') == 'https' || env('HTTP_X_FORWARDED_SCHEME') == 'https')
                $protocol = "https";
            $url = $protocol . "://" . env('HTTP_HOST');
            if (!ping($url)) {
                $uInfo = parse_url($url);
                if (isset($uInfo['scheme'], $uInfo['host']))
                    $url = $uInfo['scheme'] . '://' . $uInfo['host'];
            }
            if (!ping($url))
                $url = config('jellyfin.external_url');
        }

        if(!empty($path)) {
            $path = !str_starts_with($path, '/') ? '/' . $path : $path;
            $path .= !empty($query) ? '?' . http_build_query($query, '', '&') : '';
        }
        return $url . $path;
    }
}

if (!function_exists('jellyfin_client')) {
    function jellyfin_client($request){
        $header = $request->header();
        $userAgent = str_replace(' ', '-',@explode('/', trim(@$header['user-agent'][0]))[0] ?? "");
        if (!empty($userAgent) && strtoupper($userAgent) !== "MOZILLA") //Fix for Unofficial Clients
            return false;
        return true;
    }
}

if (!function_exists('jellyfin_response')) {
    function jellyfin_response($request){
        $url = app_url($request->path());
        $query = array_merge($request->query(), ['jpCall' => true]);
        return redirect($url . '?' . http_build_query($query, '', '&'), 302, $request->header());
    }
}

if (!function_exists('jellyfin_url')) {
    function jellyfin_url($path = "", array $query = []){
        $url = jp_config('jellyfin.external_url');
        $path = !str_starts_with($path, '/') ? '/' . $path : $path;
        $query = array_merge($query, ['jpCall' => true]);
        return $url . $path . '?' . http_build_query($query, '', '&');
    }
}

if (!function_exists('get_last_url')) {
    function get_last_url($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 200 && $httpCode < 300)
            return curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        return null;
    }
}

if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        else if (isset($_SERVER['HTTP_X_REAL_IP']))
            $ipaddress = $_SERVER['HTTP_X_REAL_IP'];
        else if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}


if (!function_exists('remove_dir')) {
    function remove_dir($path){
        if(file_exists($path)){
            system("rm -rf ".escapeshellarg($path));
        }
    }
}

if (!function_exists('save_image')) {
    function save_image($inPath, $outPath)
    {
        $in = fopen($inPath, "rb");
        $out = fopen($outPath, "wb");
        while ($chunk = fread($in, 8192)) {
            fwrite($out, $chunk, 8192);
        }
        fclose($in);
        fclose($out);
    }
}

if (!function_exists('filter_flag')) {
    function filter_flag(array $array){
        // Pattern per identificare le bandiere
        $pattern = '/\x{1F1E6}-\x{1F1FF}\x{1F1E6}-\x{1F1FF}/u';

        // Filtra solo le emoji che corrispondono al pattern
        return array_filter($array, function ($emoji) use ($pattern) {
            return preg_match($pattern, $emoji);
        });
    }
}

if (!function_exists('text2emoji')) {
    function text2emoji(string $string){
        // Pattern per identificare gli emoji (Unicode range)
        $pattern = '/[\x{1F600}-\x{1F64F}' .    // Emoticon
            '\x{1F300}-\x{1F5FF}' .    // Simboli e pittogrammi
            '\x{1F680}-\x{1F6FF}' .    // Trasporti e simboli vari
            '\x{1F700}-\x{1F77F}' .    // Simboli alchemici
            '\x{1F780}-\x{1F7FF}' .    // Simboli vari
            '\x{1F800}-\x{1F8FF}' .    // Simboli supplementari
            '\x{2600}-\x{26FF}' .      // Simboli vari (sole, nuvola, ecc.)
            '\x{2700}-\x{27BF}' .      // Dingbats
            '\x{FE00}-\x{FE0F}' .      // Variazioni
            '\x{1F900}-\x{1F9FF}' .    // Emoji supplementari
            '\x{1FA70}-\x{1FAFF}' .    // Oggetti emoji
            '\x{200D}' .               // Zero Width Joiner
            '\x{1F1E6}-\x{1F1FF}]{1,2}/u' // Bandiere (due caratteri consecutivi)
        ;


        // Esegui il match per trovare tutte le occorrenze
        preg_match_all($pattern, $string, $matches);

        // Restituisci un array con gli emoji trovati
        return @$matches[0] ?? [];
    }
}

if (!function_exists('lang2flag')) {
    function lang2flag(string $countryCode) {
        try {
            if(strtoupper($countryCode) === "EN")
                return "🇬🇧";

            return (string)preg_replace_callback(
                '/./',
                static fn(array $letter) => mb_chr(ord($letter[0]) % 32 + 0x1F1E5),
                strtoupper($countryCode)
            );
        }catch (\Exception $e){}
        return null;
    }
}

if (!function_exists('flag2lang')) {
    function flag2lang(string $string) {
        return preg_replace_callback('/[\x{1F1E6}-\x{1F1FF}]{2}/u', function ($match) {
            // Ottieni i caratteri Unicode delle due parti della bandiera
            $emoji = $match[0];
            $codepoints = unpack('N*', mb_convert_encoding($emoji, 'UTF-32', 'UTF-8'));

            // Converti i codepoints Unicode nel codice paese
            $offset = 0x1F1E6; // Offset di Unicode per le lettere delle bandiere
            $primaLettera = $codepoints[1] - $offset + 65; // A=65 in ASCII
            $secondaLettera = $codepoints[2] - $offset + 65;

            $codicePaese = chr($primaLettera) . chr($secondaLettera);
            $codicePaese = preg_replace("[^A-Za-z]", "", $codicePaese);
            $codicePaese = strtolower($codicePaese) == "gb" ? "en" : $codicePaese;

            // Converti il codice paese in minuscolo per la lingua (es. IT -> ita)
            return strtoupper($codicePaese);
        }, $string);
    }
}

if (!function_exists('local_ip')) {
    function local_ip(string $ip = null){
        if(!empty($ip)){
            if(filter_var($ip, FILTER_VALIDATE_IP)) {
                $reserved_ips = array( // not an exhaustive list
                    '167772160' => 184549375,  /*    10.0.0.0 -  10.255.255.255 */
                    '3232235520' => 3232301055, /* 192.168.0.0 - 192.168.255.255 */
                    '2130706432' => 2147483647, /*   127.0.0.0 - 127.255.255.255 */
                    '2851995648' => 2852061183, /* 169.254.0.0 - 169.254.255.255 */
                    '2886729728' => 2887778303, /*  172.16.0.0 -  172.31.255.255 */
                    '3758096384' => 4026531839, /*   224.0.0.0 - 239.255.255.255 */
                );
                $ip_long = sprintf('%u', ip2long($ip));
                foreach ($reserved_ips as $ip_start => $ip_end) {
                    if (($ip_long >= $ip_start) && ($ip_long <= $ip_end)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}

if (!function_exists('intneg')) {
    function intneg($number) {
        return -1 * abs($number);
    }
}

if (!function_exists('dir_tree')) {
    function dir_tree(string $path, bool $fullpath = false) {
        $rdi = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $rii = new \RecursiveIteratorIterator($rdi);
        $tree = [];
        foreach ($rii as $file) {
            $filename = $fullpath ? $file->getPathname() : $file->getFilename();
            if (str_ends_with($filename, '.')) continue;
            $path = $file->isDir() ? array($filename => array()) : array($filename);
            for ($depth = $rii->getDepth() - 1; $depth >= 0; $depth--) {
                $folder = $rii->getSubIterator($depth)->current();
                $foldername = $fullpath ? $folder->getPathname() : $folder->getFilename();
                $path = array($foldername => $path);
            }
            $tree = array_merge_recursive($tree, $path);
        }
        return $tree;
    }
}

if (!function_exists('get_files_from_dir')) {
    function get_files_from_dir(string $path, array $extensions = [], $limit = null) {
        $rdi = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $rii = new \RecursiveIteratorIterator($rdi);
        $outcome = [];
        foreach ($rii as $file) {
            if($file->isDir()){
                array_merge($outcome, get_files_from_dir($file->getPathname()));
            }else{
                if(!empty($extensions))
                    if(!in_array($file->getExtension(), $extensions))
                        continue;

                $outcome[] = $file->getPathname();
            }
        }
        if(isset($limit))
            $outcome = array_slice($outcome, 0, $limit);
        return $outcome;
    }
}

if (!function_exists('download_file_from_url')) {
    function download_file_from_url(string $url, string $destinationPath)
    {
        set_time_limit(3600);
        ini_set('memory_limit', -1);
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3600);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $filenameFromUrl = basename(parse_url($lastUrl, PHP_URL_PATH));

            if ($httpCode >= 200 && $httpCode < 300) {
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $headerSize);
                $body = substr($response, $headerSize);

                // Tenta di ottenere il nome del file dall'header Content-Disposition
                if (preg_match('/Content-Disposition:.*?filename=["\']?([^"\'\s]+)["\']?/', $header, $matches)) {
                    $filename = $matches[1];
                } else {
                    // Se Content-Disposition non trovato, usa il nome del file dall'URL (meno affidabile)
                    $filename = $filenameFromUrl;
                }

                if(!str_ends_with($destinationPath, "/"))
                    $destinationPath .= "/";

                $destinationFile = $destinationPath . $filename;
                if (file_put_contents($destinationFile, $body))
                    return $destinationFile;
            }
            curl_close($ch);
        } catch (\Exception $e) {}
        return false;
    }
}

if (!function_exists('clean_title')) {
    function clean_title(string $string){
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
        return str_replace('-', ' ', preg_replace('/-+/', '-', $string)); // Replaces multiple hyphens with single one.
    }
}

if (!function_exists('json_from_file')) {
    function json_from_file(string $filename) {
        $outcome = [];
        try{
            $outcome = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', file_get_contents($filename)), true);
        }catch (\Exception $e){}
        return $outcome;
    }
}

if (!function_exists('json_to_file')) {
    function json_to_file(string $filename, array $array) {
        return file_put_contents($filename, json_encode($array, JSON_PRETTY_PRINT));
    }
}

if (!function_exists('array_filter_recursively')) {
    function array_filter_recursively(array $array): array{
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_filter_recursively($value);

                if (empty($array[$key])) {
                    unset($array[$key]);
                }
            } elseif ($value === null) {
                unset($array[$key]);
            }
        }
        return $array;
    }
}

if(!function_exists('is_base64')) {
    function is_base64(string $string)
    {
        if(!is_md5($string)) {
            $str = base64_decode($string, true);
            if ($str !== false) {
                $b64 = base64_encode($str);
                if ($string === $b64)
                    return true;
            }
        }
        return false;
    }
}

if(!function_exists('is_md5')) {
    function is_md5(string $string){
        return strlen($string) === 32 && preg_match('/^[a-f0-9]{32}$/', $string);
    }
}

if(!function_exists('is_json')) {
    function is_json(string $string){
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('array_sort')) {
    function array_sort(array $array, string $key, $type = SORT_ASC) {
        $keys = array_column($array, $key);
        array_multisort($keys, $type, $array);
        return $array;
    }
}

if (!function_exists('str_contains_arr')) {
    function str_contains_arr($str, array $arr){
        foreach($arr as $a) {
            if (stripos(strtolower($str), strtolower($a)) !== false) return true;
        }
        return false;
    }
}

if(!function_exists('jellyfin_date')){
    function jellyfin_date($date) : string {
        $utc = Carbon::parse($date)->utc();
        return $utc->format('Y-m-d\TH:i:s').'.'.sprintf('%07d', (int) ($utc->micro / 10)).'Z';
    }
}

if (!function_exists('task_execution')) {
    function task_execution($command, $type = "start", $date = null) {
        if(!file_exists(config('logging.channels.tasks.path')))
            json_to_file(config('logging.channels.tasks.path'), []);

        $executions = json_from_file(config('logging.channels.tasks.path'));
        if(isset($date)){
            $utc = $date->utc();
            $executions[$command][$type] = $utc->format('Y-m-d\TH:i:s').'.'.sprintf('%07d', (int) ($utc->micro / 10)).'Z';
            $executions[$command]['status'] = ($type == "start") ? "Running" : "Completed";
            #Log::info("[Tasks] saving tasks log:\n".json_encode($executions));
            if(!empty($executions))
                json_to_file(config('logging.channels.tasks.path'), $executions);
        }
        if(!isset($executions[$command][$type])){
            $executions[$command][$type] = \Carbon\Carbon::now()->subYear()->utc()->format('Y-m-d\TH:i:s').'.'.sprintf('%07d', 0).'Z';
            if($type == "status")
                $executions[$command][$type] = "Completed";
        }
        return $executions[$command][$type];
    }
}

if (!function_exists('task_start')) {
    function task_start($command, \Carbon\Carbon $date = null) {
        return task_execution($command, 'start', $date);
    }
}

if (!function_exists('task_end')) {
    function task_end($command, \Carbon\Carbon $date = null) {
        return task_execution($command, 'end', $date);
    }
}

if (!function_exists('task_status')) {
    function task_status($command) {
        return task_execution($command, 'status');
    }
}

if (!function_exists('get_item_path')) {
    function get_item_path(string $itemPath){
        try {
            $path = str_replace(jp_data_path(''), '', $itemPath);
            $path = parse_url($path, PHP_URL_PATH);
            $parts = explode('/', $path);
            $parts = array_slice($parts, 0, 3);
            return implode('/', $parts);
        }catch (\Exception $e){}
        return null;
    }
}

if (!function_exists('get_imdbid_from_path')) {
    function get_imdbid_from_path($path) {
        try {
            $path = get_item_path($path);
            $parts = explode('/', $path);
            $imdbId = $parts[array_key_last($parts)];
            return str_starts_with($imdbId, 'tt') ? $imdbId : null;
        }catch (\Exception $e){}
        return null;
    }
}

if (!function_exists('parse_stream_url')) {
    function parse_stream_url($url, $seasonNumber = null, $episodeNumber = null, $defaultEpisodeNumber = null)
    {
        preg_match_all('/\{([^}]+)\}/', $url, $matches);
        if (isset($matches[1])) {
            $replacements = [
                'ep_' => function ($match, $parts, $url) use ($defaultEpisodeNumber) {
                    return !empty($parts) ? sprintf($parts[0], $defaultEpisodeNumber) : $defaultEpisodeNumber;
                },
                'epc_' => function ($match, $parts, $url) use ($episodeNumber) {
                    return !empty($parts) ? sprintf($parts[0], $episodeNumber) : $episodeNumber;
                },
                'sn_' => function ($match, $parts, $url) use ($seasonNumber) {
                    return !empty($parts) ? sprintf($parts[0], $seasonNumber) : $seasonNumber;
                },
                'epf_start_' => function ($match, $parts, $url) use ($defaultEpisodeNumber) {
                    $outcome = (floor($defaultEpisodeNumber / $parts[0]) * $parts[0]) + 1;
                    if (isset($parts[1]))
                        $outcome = sprintf($parts[1], $outcome);
                    return $outcome;
                },
                'epf_end_' => function ($match, $parts, $url) use ($defaultEpisodeNumber) {
                    $outcome = ceil($defaultEpisodeNumber / $parts[0]) * $parts[0];
                    if (isset($parts[1]))
                        $outcome = sprintf($parts[1], $outcome);
                    return $outcome;
                },
                'epcf_start_' => function ($match, $parts, $url) use ($episodeNumber) {
                    $outcome = (floor($episodeNumber / $parts[0]) * $parts[0]) + 1;
                    if (isset($parts[1]))
                        $outcome = sprintf($parts[1], $outcome);
                    return $outcome;
                },
                'epcf_end_' => function ($match, $parts, $url) use ($episodeNumber) {
                    $outcome = ceil($episodeNumber / $parts[0]) * $parts[0];
                    if (isset($parts[1]))
                        $outcome = sprintf($parts[1], $outcome);
                    return $outcome;
                },
                'switch_' => function ($match, $parts, $url) {
                    $cases = explode('_or_', $match);
                    foreach ($cases as $case) {
                        $tempUrl = str_replace('{switch_' . $match . '}', $case, $url);
                        if (test_url($tempUrl))
                            return $case;
                    }
                    return '';
                }
            ];

            foreach ($replacements as $prefix => $callback) {
                foreach ($matches[1] as $match) {
                    if (str_starts_with($match, $prefix)) {
                        $match2 = substr($match, strlen($prefix));
                        $parts = explode('_', $match2);
                        $replace = $callback($match2, $parts, $url);
                        $url = str_replace('{' . $match . '}', $replace, $url);
                    }
                }
            }
        }

        if (test_url($url))
            return $url;

        return null;
    }
}

