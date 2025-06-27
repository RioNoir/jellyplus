<?php

namespace App\Services\Helpers;

class FileHelper
{

    public static function extractGZ($compressedFilePath, $uncopressedFilePath = null, $deleteCompressed = false){
        $buffer_size = 4096;
        if(!isset($uncopressedFilePath))
            $uncopressedFilePath = str_replace('.gz', '', $compressedFilePath);

        $file = gzopen($compressedFilePath, 'rb');
        $out_file = fopen($uncopressedFilePath, 'wb');

        while (!gzeof($file)) {
            fwrite($out_file, gzread($file, $buffer_size));
        }

        fclose($out_file);
        gzclose($file);

        if($deleteCompressed)
            unlink($compressedFilePath);

        return $uncopressedFilePath;
    }

    public static function parseDelimitedFile($filePath, $delimiter = ';', $excludeFirst = false)
    {
        ini_set('memory_limit', '-1');
        $outcome = [];
        $fp = fopen($filePath, 'r');
        while ( !feof($fp) )
        {
            $line = fgets($fp, 2048);
            $data = str_getcsv($line, $delimiter);
            $outcome[] = $data;
        }
        if($excludeFirst)
            array_shift($outcome);
        return $outcome;
    }

    public static function splitFile($filePath, $sizePerFile = "2048m"){
        $splittedFolder = $filePath.".split/";

        if(!file_exists($splittedFolder))
            mkdir($splittedFolder, 0777, true);

        exec('split -d -b '.$sizePerFile.' '.$filePath.' '.$splittedFolder);

        return self::getFilesList($splittedFolder);
    }

    public static function getFilesList($folderPath){
        if(!str_ends_with($folderPath, "/")) $folderPath .= "/";
        if(!file_exists($folderPath)) return [];
        return array_values(array_map(function($file) use ($folderPath) {
            return $folderPath.$file;
        }, array_diff(scandir($folderPath), array('.', '..'))));
    }

}
