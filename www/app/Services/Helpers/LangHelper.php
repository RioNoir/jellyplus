<?php

namespace App\Services\Helpers;

use App\Services\Jellyfin\JellyfinApiManager;

class LangHelper
{
    public static function getFullLanguageList($threeLetterFormat = true){
        $langs = [];
        $api = new JellyfinApiManager();
        $api->setAuthenticationByApiKey();
        $cultures = $api->getCultures();

        if(!empty($cultures)){
            foreach ($cultures as $culture){
                if($threeLetterFormat){
                    $langs[$culture['ThreeLetterISOLanguageName']] = $culture['DisplayName'];
                }else{
                    $langs[$culture['TwoLetterISOLanguageName']] = $culture['DisplayName'];
                }
            }
        }
        return $langs;
    }
}
