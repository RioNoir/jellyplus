<?php

namespace App\Services\Helpers;

use Illuminate\Support\Str;

class ImageHelper
{

    public static function getImageIdByParams(array $params){
        return base64_encode(json_encode($params));
    }

    public static function getImageById(string $imageId, $fillHeight = 270, $fillWidth = 480){
        try {
            $data = json_decode(base64_decode($imageId), true);
            if(isset($data['title'])){

                $imagesPath = null;
                if(isset($data['catalog'])){
                    $type = Str::plural(explode('.', $data['catalog'])[0]);
                    $path = jp_config('jellyfin.'.$type.'_path');

                    if($path && file_exists($path))
                        $imagesPath = $path;
                }

                return self::getImageByName($data['title'], $fillHeight, $fillWidth, $imagesPath);
            }
        }catch (\Exception $e){}
        return null;
    }

    public static function getImageByName(string $imageText, $fillHeight = null, $fillWidth = null, $imagesPath = null){
        $imageId = md5($imageText)."_w".$fillWidth."_h".$fillHeight;

        if(file_exists(jp_data_path('app/images/' . $imageId . '.png')))
            return jp_data_path('app/images/' . $imageId . '.png');

        $image = self::getRandomImageFromLibrary($imagesPath);
        if(isset($image)){
            try {
                $fileInfo = pathinfo($image);

                if ($fileInfo['extension'] == 'png') {
                    $image = imagecreatefrompng($image);
                } else {
                    $image = imagecreatefromjpeg($image);
                }

                if(isset($fillWidth) || isset($fillHeight)) {

                    $srcWidth = imagesx($image);
                    $srcHeight = imagesy($image);

                    if(!isset($fillHeight))
                        $fillHeight = $srcHeight * $srcWidth / $srcWidth;

                    // Calcola il rapporto scala per riempire il box (anche se esce)
                    $scale = max($fillWidth / $srcWidth, $fillHeight / $srcHeight);

                    // Nuove dimensioni proporzionate
                    $resizedWidth = ceil($srcWidth * $scale);
                    $resizedHeight = ceil($srcHeight * $scale);

                    // Ridimensiona l'immagine
                    $resized = imagecreatetruecolor($resizedWidth, $resizedHeight);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $resizedWidth, $resizedHeight, $srcWidth, $srcHeight);

                    // Ora ritaglia al centro per ottenere esattamente 300x300
                    $cropX = ($resizedWidth - $fillWidth) / 2;
                    $cropY = ($resizedHeight - $fillHeight) / 2;

                    $image = imagecrop($resized, [
                        'x' => $cropX,
                        'y' => $cropY,
                        'width' => $fillWidth,
                        'height' => $fillHeight
                    ]);
                }

                $width = imagesx($image);
                $height = imagesy($image);

                //Dark
                imagefilter($image, IMG_FILTER_BRIGHTNESS, -60);
                imagefilter($image, IMG_FILTER_BRIGHTNESS, -20);

                //Aggiunta testo
                $font = "/var/src/fonts/Roboto-ExtraBold.ttf";
                $color = imagecolorallocate($image, 255, 255, 255);
                $maxFontSize = 35;
                $fontSize = $maxFontSize;
                $angle = 0;
                $padding = $width * 0.1;

                do {
                    $bbox = imagettfbbox($fontSize, 0, $font, $imageText);
                    $textWidth = $bbox[2] - $bbox[0];
                    if ($textWidth <= ($width - 2 * $padding)) {
                        break;
                    }
                    $fontSize--;
                } while ($fontSize > 5); // Limite minimo

                // Calcolo posizione centrata
                $textHeight = $bbox[1] - $bbox[7];
                $x = ($width - $textWidth) / 2;
                $y = (($height + $textHeight) - 10) / 2; // Centra verticalmente

                // Scrive il testo
                imagettftext($image, $fontSize, $angle, $x, $y, $color, $font, $imageText);

                if (!file_exists(jp_data_path('app/images')))
                    mkdir(jp_data_path('app/images'), 0777, true);

                //Save
                $imageId = md5($imageText)."_w".$width."_h".$height;
                $imagePath = jp_data_path('app/images/' . $imageId . '.png');
                imagepng($image, $imagePath);

                return $imagePath;

            }catch (\Exception $e){}
        }
        return null;
    }

    public static function getRandomImageFromLibrary($imagesPath = null){
        if(!isset($imagesPath))
            $imagesPath = jp_data_path('library/');

        $images = get_files_from_dir($imagesPath, ['jpg', 'jpeg', 'png']);

        if(count($images) <= 10)
            $images = get_files_from_dir(jp_data_path('library/'), ['jpg', 'jpeg', 'png']);

        foreach ($images as $key => $image) {
            $fileInfo = pathinfo($image);
            if(!str_contains_arr($fileInfo['filename'], ['thumb', 'backdrop']))
                unset($images[$key]);
        }

        if(!empty($images))
            return $images[array_rand($images)];
        return null;
    }

}
