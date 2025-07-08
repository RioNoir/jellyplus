<?php

namespace App\Services\Jellyfin;

use App\Models\Jellyfin\ApiKeys;
use App\Services\Api\ApiManager;
use App\Services\Api\ApiResponse;
use App\Services\Jellyfin\lib\Movies;
use App\Services\Jellyfin\lib\TVSeries;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class JellyfinApiManager extends ApiManager
{
    protected $endpoint, $headers, $accessToken;

    public function __construct(array $headers = []){
        $this->timeout = 30;
        $this->endpoint = jp_config('jellyfin.url');
        $this->headers = request()->headers->all();

        if(!empty($headers))
            $this->headers = $headers;
    }

    public function setTimeout($timeout){
        $this->timeout = $timeout;
        return $this;
    }

    public function getStreamingLanguageByUser(string $userId = null){
        $streamLang = jp_config('stream.lang');

//        if(!isset($userId)) {
//            $user = $this->getAuthUser();
//            if (!empty($user))
//                $userId = $user['Id'];
//        }

        if(isset($userId))
            $user = $this->getUserById($userId);

        if(!empty($user) && !empty($user['Configuration']['AudioLanguagePreference']))
            $streamLang = $user['Configuration']['AudioLanguagePreference'];

        return $streamLang;
    }

    public function getApiKeys(){
        return $this->apiCall('/Auth/Keys', 'GET');
    }

    public function createApiKey(string $apiKeyName){
        $query = ['app' => $apiKeyName];
        return $this->apiCall('/Auth/Keys?'.http_build_query($query), 'POST');
    }

    public function deleteApiKey(string $apiKeyId){
        return $this->apiCall('/Auth/Keys/'.$apiKeyId, 'DELETE');
    }

    public function createApiKeyIfNotExists(string $apiKeyName){
        $keys = $this->getApiKeys();
        if(!empty($keys['Items'])){
            $keys = array_filter(array_map(function($key) use($apiKeyName){
                return trim($key['AppName']) == trim($apiKeyName) ? $key : null;
            }, $keys['Items']));
            if(count($keys) > 0)
                return $keys[array_key_first($keys)];
        }
        $this->createApiKey($apiKeyName);
        $keys = $this->getApiKeys();
        return collect($keys)->where('AppName', $apiKeyName)->first();
    }

    public function testApiKey($apiKey = null){
        $this->setAuthenticationByApiKey($apiKey);
        return !empty($this->getUsers());
    }

    public function getItemFromQuery(string $itemId, array $query = []): ?array {
        if (isset($query['userId']) || isset($query['UserId'])) {
            $userId = @$query['userId'] ?? @$query['UserId'];
            $response = $this->getUsersItem($userId, $itemId, $query);
        } else {
            $response = $this->getItem($itemId, $query);
        }
        return $response;
    }

    public function getItems(array $query = []){
        return $this->apiCall('/Items', 'GET', $query);
    }

    public function getItem(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId, 'GET', $query);
    }

    public function postItem(string $itemId, array $data = []){
        return $this->apiCall('/Items/'.$itemId, 'POST_BODY', $data);
    }

    public function deleteItem(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId, 'DELETE', $query);
    }

    public function getItemImage(string $itemId, string $imageType, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/Images/'.$imageType, 'GET', $query);
    }

    public function getItemsLatest(array $query = []){
        return $this->apiCall('/Items/Latest', 'GET', $query);
    }

    public function getSeasons(string $itemId, array $query = []){
        return $this->apiCall('/Shows/'.$itemId.'/Seasons', 'GET', $query);
    }

    public function getEpisodes(string $itemId, array $query = []){
        return $this->apiCall('/Shows/'.$itemId.'/Episodes', 'GET', $query);
    }

    public function getUsersItemsLatest(string $userId, array $query = []){
        return $this->apiCall('/Users/'.$userId.'/Items/Latest', 'GET', $query);
    }

    public function getItemDownload(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/Download', 'GET', $query, [], true);
    }

    public function getItemFile(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/File', 'GET', $query, [], true);
    }

    public function getItemPlaybackInfo(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/PlaybackInfo', 'GET', $query);
    }

    public function postItemPlaybackInfo(string $itemId, array $query = [], array $data = []){
        return $this->apiCall('/Items/'.$itemId.'/PlaybackInfo?'.http_build_query($query), 'POST_BODY', $data);
    }

    public function getItemThemeMedia(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/ThemeMedia', 'GET', $query);
    }

    public function getItemSimilar(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/Similar', 'GET', $query);
    }

    public function getUsersItem(string $userId, string $itemId, array $query = []){
        return $this->apiCall('/Users/'.$userId.'/Items/'.$itemId, 'GET', $query);
    }

    public function getUsersItems(string $userId, array $query = []){
        return $this->apiCall('/Users/'.$userId.'/Items', 'GET', $query);
    }

    public function refreshItemMetadata(string $itemId, array $query = []){
        if(!empty($query))
            $query = [
                'Recursive' => 'true',
                'ImageRefreshMode' => 'FullRefresh',
                'MetadataRefreshMode' => 'FullRefresh',
                'ReplaceAllImages' => 'false',
                'RegenerateTrickplay' => 'false',
                'ReplaceAllMetadata' => 'true'
            ];
        return $this->apiCall('/Items/'.$itemId.'/Refresh?'.http_build_query($query), 'POST');
    }

    public function setItemImage(string $itemId, string $imageType, string $imageBinary){
        return $this->apiCall('/Items/'.$itemId.'/Images/'.$imageType, 'POST_BODY', $imageBinary);
    }

    public function deleteItemImage(string $itemId, string $imageType){
        return $this->apiCall('/Items/'.$itemId.'/Images/'.$imageType, 'DELETE');
    }

    public function setItemFavorite(string $itemId, string $userId){
        $query = ['userId' => $userId, 'spCall' => true];
        return $this->apiCall('/UserFavoriteItems/'.$itemId.'?'.http_build_query($query), 'POST_JSON', []);
    }

    public function removeItemFavorite(string $itemId, string $userId){
        $query = ['userId' => $userId, 'spCall' => true];
        return $this->apiCall('/UserFavoriteItems/'.$itemId.'?'.http_build_query($query), 'DELETE', []);
    }

    public function setSessionsPlaying(array $query = []){
        return $this->apiCall('/Sessions/Playing', 'POST_BODY', $query);
    }

    public function setSessionsPlayingProgress(array $query = []){
        return $this->apiCall('/Sessions/Playing/Progress', 'POST_BODY', $query);
    }

    public function getVideoStream(string $itemId, array $query = []){
        return $this->apiCall('/Videos/'.$itemId.'/stream', 'GET', $query);
    }

    public function getPersons(array $query = []){
        return $this->apiCall('/Persons', 'GET', $query);
    }

    public function getArtists(array $query = []){
        return $this->apiCall('/Artists', 'GET', $query);
    }

    public function getPlugins(array $query = []){
        return $this->apiCall('/Plugins', 'GET', $query);
    }

    public function getPackages(array $query = []){
        return $this->apiCall('/Packages', 'GET', $query);
    }

    public function getPackagesByName(string $name, array $data){
        return $this->apiCall('/Packages/'.$name, 'GET', $data);
    }

    public function getPackagesRepositories(){
        return $this->apiCall('/Repositories');
    }

    public function getScheduledTasks(){
        return $this->apiCall('/ScheduledTasks');
    }

    public function getScheduledTask(string $taskId){
        return $this->apiCall('/ScheduledTasks/'.$taskId);
    }

    public function postScheduledTaskRunning(string $taskId){
        return $this->apiCall('/ScheduledTasks/Running/'.$taskId, 'POST');
    }

    public function deleteScheduledTaskRunning(string $taskId){
        return $this->apiCall('/ScheduledTasks/Running/'.$taskId, 'DELETE');
    }

    public function getConfiguration(){
        return $this->apiCall('/System/Configuration');
    }

    public function updateConfiguration(array $configuration){
        $data = array_merge($this->getConfiguration(), $configuration);
        return $this->apiCall('/System/Configuration', 'POST_BODY', $data);
    }

    public function getWebconfig(){
        return $this->apiCall('/web/config.json');
    }

    public function getSystemInfo(){
        return $this->apiCall('/System/Info');
    }

    public function getSystemInfoPublic(){
        return $this->apiCall('/System/Info/Public');
    }

    public function getSystemConfiguration(string $key = ""){
        return $this->apiCall('/System/Configuration/'.$key);
    }

    public function postSystemConfiguration(string $key, array $data = []){
        return $this->apiCall('/System/Configuration/'.$key, 'POST_BODY', $data);
    }

    public function getPluginConfiguration(array $data = []){
        return $this->apiCall('/web/configurationpages?'.http_build_query($data));
    }

    public function getBranding(){
        return $this->apiCall('/Branding/Configuration');
    }

    public function updateBranding(array $configuration){
        $data = array_merge($this->getBranding(), $configuration);
        return $this->apiCall('/System/Configuration/branding', 'POST_BODY', $data);
    }

    public function getVirtualFolders(){
        return $this->apiCall('/Library/VirtualFolders');
    }

    public function addVirtualFolder(string $folderName, string $folderPath, string $collectionType, array $data = []){
        $query = [
            'name' => $folderName,
            'collectionType' => $collectionType,
        ];
        if(empty($data)){
            $data = ($collectionType == "movies") ? Movies::$FOLDER_CONFIG : TVSeries::$FOLDER_CONFIG;
            $data['collectionType'] = $collectionType;
            $data['name'] = $folderName;
            $data['LibraryOptions']['PathInfos'][0]['Path'] = $folderPath;
        }
        return $this->apiCall('/Library/VirtualFolders?'.http_build_query($query), 'POST_JSON', $data);
    }

    public function createVirtualFolder(array $query, array $data = []){
        return $this->apiCall('/Library/VirtualFolders?'.http_build_query($query), 'POST_JSON', $data);
    }

    public function deleteVirtualFolder(string $folderName){
        $query = ['name' => $folderName];
        return $this->apiCall('/Library/VirtualFolders?'.http_build_query($query), 'DELETE');
    }

    public function createVirtualFolderIfNotExist(string $folderName, string $folderPath, string $collectionType){
        $virtualFolders = $this->getVirtualFolders();
        if(!empty($virtualFolders)){
            $virtualFolders = array_filter(array_map(function($folder) use($folderPath){
                return in_array($folderPath, $folder['Locations']) ? $folder : null;
            }, $virtualFolders));
            if(!empty($virtualFolders))
                return $virtualFolders[array_key_first($virtualFolders)];
        }
        $this->addVirtualFolder($folderName, $folderPath, $collectionType);
        $virtualFolders = $this->getVirtualFolders();
        return collect($virtualFolders)->where('Name', $folderName)
            ->where('CollectionType', $collectionType)->first();
    }

    public function deleteVirtualFolderIfNotPrimary(string $folderName = null){
        if(isset($folderName)) {
            $virtualFolders = $this->getVirtualFolders();
            $virtualFolders = array_filter(array_map(function ($folder) use($folderName){
                return md5($folder['Name']) == md5($folderName) ? $folder : null;
            }, $virtualFolders));

            if(!empty($virtualFolders)){
                $virtualFolder = $virtualFolders[array_key_first($virtualFolders)];

                if(in_array(jp_config('jellyfin.movies_path'), $virtualFolder['Locations']) ||
                    in_array(jp_config('jellyfin.series_path'), $virtualFolder['Locations']))
                    return [];

                return $this->deleteVirtualFolder($virtualFolder['Name']);
            }
        }
        return [];
    }

    public function reportsNewMovieAdded(string $imdbId){
        $query = [
            'imdbId' => $imdbId,
        ];
        return $this->apiCall('/Library/Movies/Added?'.http_build_query($query), 'POST');
    }

    public function startLibraryScan(){
        return $this->apiCall('/Library/Refresh?'.http_build_query(['spCall' => true]), 'POST');
    }

    public function getCultures(){
        return $this->apiCall('/Localization/cultures', 'GET');
    }

    public function createUserIfNotExist(string $username, string $password){
        $users = $this->getUsers();
        $user = collect($users)->where('Name', $username)->first();
        if(!isset($user)) {
            $data = [
                'Name' => $username,
                'Password' => $password,
            ];
            $user = $this->apiCall('/Users/New', 'POST_BODY', $data);
        }
        return $user;
    }

    public function getUserViews(array $query = []){
        return $this->apiCall('/UserViews?'.http_build_query($query), 'GET');
    }

    public function getUser(string $userId){
        return $this->apiCall('/Users/'.$userId, 'GET');
    }

    public function getUsers(){
        return $this->apiCall('/Users', 'GET');
    }

    public function getUserById(string $userId){
        return $this->apiCall('/Users/'.$userId, 'GET');
    }

    public function getUserSettings(string $userId){
        $query = ['userId' => $userId, 'client' => 'emby'];
        return $this->apiCall('/DisplayPreferences/usersettings', 'GET', $query);
    }

    public function getAuthUser(){
        return $this->apiCall('/Users/Me', 'GET');
    }

    public function getStartupUser(){
        return $this->apiCall('/Startup/User?'.http_build_query(['spCall' => true]), 'GET');
    }

    public function postStartupUsers(array $data = []){
        return $this->apiCall('/Startup/User?'.http_build_query(['spCall' => true]), 'POST_JSON', $data);
    }

    public function authenticateUser(string $username, string $password){
        $data = [
            'Username' => $username,
            'Pw' => $password,
        ];
        return $this->apiCall('/Users/AuthenticateByName', 'POST_BODY', $data);
    }

    public function updateUserPolicy(string $userId, array $data){
        return $this->apiCall('/Users/'.$userId.'/Policy', 'POST_JSON', $data);
    }

    public static function selfCall(Request $request, array $data = [], $returnBody = false){
        $uri = $request->path();
        $uri = !str_starts_with('/', $uri) ? '/' . $uri : $uri;
        $url = jp_config('jellyfin.url').$uri.'?'.http_build_query($request->query());
        $data = !empty($data) ? $data : $request->post();
        $response = static::call($url, $request->getMethod(), $data, $request->header(), $returnBody);
        return $response ?? [];
    }

    public function setAuthenticationByApiKey($apiKey = null){
        if(!isset($apiKey))
            $apiKey = jp_config('api_key');
        if(isset($apiKey)){
            if(isset($this->headers['Authorization']))
                unset($this->headers['Authorization']);
            if(isset($this->headers['authorization']))
                unset($this->headers['authorization']);
            if(isset($this->headers['x-emby-token']))
                unset($this->headers['x-emby-token']);
            if(isset($this->headers['X-Emby-Token']))
                unset($this->headers['X-Emby-Token']);

            $this->headers['X-Emby-Token'] = $apiKey;
        }
    }

    protected function apiCall(string $uri, string $method = 'GET', array|string $data = [], array $headers = [], $returnBody = false)
    {
        $default_headers = [
            'Content-Type' => 'application/json'
        ];

        if(!empty($this->headers))
            $default_headers = $this->headers;

        $headers = array_merge($default_headers, $headers);
        $response = parent::apiCall($uri, $method, $data, $headers);

        if($response->hasPositiveResponse()) {
            if($returnBody)
                return $response->getBody();
            return $response->getJson();
        }
        return [];
    }

}
