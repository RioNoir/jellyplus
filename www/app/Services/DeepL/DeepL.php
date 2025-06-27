<?php
namespace App\Services\DeepL;
use stdClass;
define('DEEPL_API_URL', 'https://api.deepl.com/v2/translate');
define('DEEPL_FREE_API_URL', 'https://api-free.deepl.com/v2/translate');
define('DEEPL_AUTH_KEY', '4adf5cdd-7317-e881-1c83-75ca2614aae8');
define('DEEPL_DF_LANGUAGE_FROM', 'EN');
define('DEEPL_DF_LANGUAGE_TO', 'IT');

class DeepL
{
    public static function translate($text, $lang_from=DEEPL_DF_LANGUAGE_FROM, $lang_to=DEEPL_DF_LANGUAGE_TO){
        $translated = self::api($text, $lang_from, $lang_to);
        return $translated;
    }

    public static function freeTranslate($text, $lang_from=DEEPL_DF_LANGUAGE_FROM, $lang_to=DEEPL_DF_LANGUAGE_TO){
        $translated = self::freeAPI($text, $lang_from, $lang_to);
        return $translated;
    }

    public static function api($text, $lang_from=null, $lang_to){
        $ch = curl_init();
        $post = 'auth_key=' . DEEPL_AUTH_KEY;
        $post .= '&text=' . urlencode($text);
        if(isset($lang_from)){
            $post .= '&source_lang=' . strtoupper($lang_from);
        }
        $post .= '&target_lang=' . strtoupper($lang_to);
        curl_setopt($ch, CURLOPT_URL, DEEPL_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($result, false);

        $translation = new stdClass();
        $translation->message = isset($result->message) ? $result->message : null;
        $translation->lang = isset($result->translations) ? $result->translations[0]->detected_source_language : null;
        $translation->text = isset($result->translations) ? $result->translations[0]->text : $text;
        $translation->translated = isset($result->translations) ? true : false;

        return $translation;
    }

    public static function freeApi($text, $lang_from=null, $lang_to){
        $ch = curl_init();
        $post = 'auth_key=' . DEEPL_AUTH_KEY;
        $post .= '&text=' . urlencode($text);
        if(isset($lang_from)){
            $post .= '&source_lang=' . strtoupper($lang_from);
        }
        $post .= '&target_lang=' . strtoupper($lang_to);
        curl_setopt($ch, CURLOPT_URL, DEEPL_FREE_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($result, false);

        $translation = new stdClass();
        $translation->message = isset($result->message) ? $result->message : null;
        $translation->lang = isset($result->translations) ? $result->translations[0]->detected_source_language : null;
        $translation->text = isset($result->translations) ? $result->translations[0]->text : $text;
        $translation->translated = isset($result->translations) ? true : false;

        return $translation;
    }

    public static function getBalance(){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.deepl.com/v2/usage');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Authorization: DeepL-Auth-Key ' . DEEPL_AUTH_KEY;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $result;
    }
}
