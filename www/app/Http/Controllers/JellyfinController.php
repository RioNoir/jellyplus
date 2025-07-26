<?php

namespace App\Http\Controllers;

use App\Jobs\CommandExecutionJob;
use App\Models\Items;
use App\Models\Users;
use App\Services\Addons\AddonsApiManager;
use App\Services\Addons\CatalogsManager;
use App\Services\Helpers\ImageHelper;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinItem;
use App\Services\Jellyfin\JellyfinManager;
use App\Services\Jellyfin\JellyfinResponse;
use App\Services\Tasks\TaskManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JellyfinController extends Controller
{

    protected JellyfinResponse $response;

    public function __construct(Request $request){
        $this->response = $request->attributes->get('jellyfin_response');
    }

    /*
     * Streams Routes
     */

    public function getVideosStream(string $itemId, Request $request){
        //Fix for Infuse and other clients
        $outcome = JellyfinItem::findById($itemId, $request->query())->getResponse(true);
        if (isset($outcome['Path']) && str_ends_with($outcome['Path'], '.strm')) {
            if(!empty($outcome['MediaSources'])) {
                $mediaSourceId = $request->get('MediaSourceId', $request->get('mediaSourceId', null));
                $mediaSource = $outcome['MediaSources'][array_key_first($outcome['MediaSources'])];
                if(isset($mediaSourceId))
                    $mediaSource = collect($outcome['MediaSources'])->where('Id', $mediaSourceId)->first();
                if(str_starts_with($mediaSource['Path'], 'http'))
                    return redirect($mediaSource['Path']);
            }
        }
        return jellyfin_response($request);
    }

    /*
     * Items Routes
     */

    public function getItems(Request $request) {
        $response = JellyfinManager::getItemsFromSearchTerm($this->response);
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function getItem(string $itemId, Request $request) {
        $response = JellyfinItem::findById($itemId, $request->query(), $this->response)->getResponse(true);
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function postItem(string $itemId, Request $request) {
        $data = $this->response->getContent(true);

        if(isset($data['People'])){
            $people = array_values(array_filter($data['People'], function($item){
               return !empty($item['ImageBlurHashes']['Primary']);
            }));
            $data['People'] = $people;
        }

        return $this->response->setContent($data)->make()->getResponse();
    }

    public function deleteItem(string $itemId, Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->getItems(['ids' => $itemId]);
        if(!empty($response['Items'])){
            foreach($response['Items'] as $item){
                $item = Items::query()->where('item_jellyfin_id', $item['Id'])->first();
                if(isset($item))
                    $item->removeFromLibrary();
                    //dispatch(new CommandExecutionJob('library:remove-item', ['--itemId' => $item->item_md5]));
            }
        }
        $response = $api->deleteItem($itemId);
        return $this->response->setBody($response)->setStatus(204)->getResponse();
    }

    public function getItemsImages(string $itemId, string $imageId, Request $request) {
        $response = $this->response->make()->getResponse();
        if($response->status() !== 200){
            $lang = app()->getLocale();
            $key = 'item_image_'.md5($lang.$itemId.$imageId.json_encode($request->query()));
            if($itemId == md5('_discover'))
                $key = 'item_image_'.md5($lang.$itemId.$imageId);

            $image = Cache::remember($key, Carbon::now()->addDay(), function () use ($itemId, $request) {
                $image = null;
                if ($itemId == md5('_discover'))
                    $image = ImageHelper::getImageByName(t("Discover"), 225, 400);

                if ($itemId == md5('_livetv'))
                    $image = ImageHelper::getImageByName(t("Live TV"), 225, 400);

                $item = Items::where('item_md5', $itemId)->first();
                if (isset($item->item_image_url))
                    $image = $item->item_image_url;

                return @file_get_contents($image);
            });

            if(!empty($image)){
                try {
                    $file_info = new \finfo(FILEINFO_MIME_TYPE);
                    $mime_type = $file_info->buffer($image);
                } catch (\Exception $e) {
                    $mime_type = "image/jpeg";
                }
                $response = response($image, 200)->header('Content-Type', $mime_type);
            }
        }
        return $response;
    }

    public function getItemsPlaybackInfo(string $itemId, Request $request) {
        JellyfinItem::findById($itemId, $request->query())->getResponse();
        return $this->response->make()->getResponse();
    }

    public function postItemsPlaybackInfo(string $itemId, Request $request) {
        set_time_limit(400);
        ini_set("memory_limit",-1);

        $data = $request->post();

        JellyfinItem::findById($itemId, $request->query())->getResponse();

        if(isset($data['MediaSourceId'])) {
            $itemData = JellyfinItem::decodeId($data['MediaSourceId']);
            $itemId = $itemData['itemId'];

            if (isset($itemData['streamId']))
                $data['MediaSourceId'] = $itemData['itemId'];

            $data['AllowVideoStreamCopy'] = true;
            $data['EnableDirectPlay'] = true;
            $data['EnableDirectStream'] = true;
        }

        $api = new JellyfinApiManager();
        $response = $api->setTimeout(300)->postItemPlaybackInfo($itemId, $request->query(), $data);

        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function getItemsDownload(string $itemId, Request $request) {
        $item = JellyfinItem::findById($itemId, $request->query())->getResponse(true);
        $url = jellyfin_url($request->path(), $request->query());
        if(str_ends_with($item['Path'], '.strm')){
            $url = file_get_contents(jellyfin_url($request->path(), $request->query()));
            $url .= '&mfp=0&download=1';
        }
        Log::info('[download]['.get_client_ip().'] Download item from '.$url);
        return redirect($url, 301);
    }

    public function getItemsThemeMedia(string $itemId, Request $request) {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            return $this->response->mergeBody([
                'SoundtrackSongsResult' => [],
                'ThemeSongsResult' => [],
                'ThemeVideosResult' => []
            ])->setStatus(200)->getResponse();
        }
        return $this->response->make()->getResponse();
    }

    public function getItemsSimilar(string $itemId, Request $request) {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            return $this->response->mergeBody([
                'Items' => [],
                'StartIndex' => 0,
                'TotalRecordCount' => 0
            ])->setStatus(200)->getResponse();
        }
        return $this->response->make()->getResponse();
    }

    public function getMetadataEditor(string $itemId, Request $request){
        $metadata = $this->response->make()->getBody(true);
        if(!empty($metadata)){
            $item = JellyfinItem::findById($itemId)->getResponse(true);
            if(isset($item)) {
                if(in_array($item['Type'], ['Movie', 'Series'])){
                    $metadata['ExternalIdInfos'][] = [
                        'Name' => 'Jellyplus',
                        'Key' => 'JP',
                        'UrlFormatString' => app_url('/web/#/configurationpage?name=JP_ITEM&itemId={0}'),
                    ];
                }
                if(in_array($item['Type'], ['Movie', 'Episode'])){
                    $metadata['ExternalIdInfos'][] = [
                        'Name' => 'Jellyplus Stream',
                        'Key' => 'JPStream',
                        'UrlFormatString' => '',
                    ];
                }
                if(in_array($item['Type'], ['Movie', 'Series', 'Season', 'Episode'])){
                    $metadata['ExternalIdInfos'][] = [
                        'Name' => 'Jellyplus Stream (Direct Url)',
                        'Key' => 'JPStreamUrl',
                        'UrlFormatString' => '{0}',
                    ];
                }
            }
        }
        return $this->response->setBody($metadata)->getResponse();
    }

    /*
     * User Routes
     */
    public function getUserViews(Request $request) {
        $views = $this->response->make()->getBody(true);

        if(!empty($views)) {
            $userId = $request->get('userId', @$request->route('userId'));
            $includeHidden = $request->get('includeHidden', false);
            $api = new JellyfinApiManager();
            $config = $api->getAuthUser();
            $excludes = @$config['Configuration']['MyMediaExcludes'];
            $order = @$config['Configuration']['OrderedViews'];
            $items = @$views['Items'] ?? [];

            foreach ($items as $key => $item) {
                $data = $api->getUsersItems($userId, [
                    'ParentId' => $item['Id'],
                    'StartIndex' => 0,
                    'Limit' => 50
                ]);

                if(@$item['CollectionType'] == "livetv"){
                    if(empty(@$item['ImageTags'])){
                        $item['ImageTags']['Primary'] = md5('_livetv');
                    }
                }

                if(empty(@$data['Items']) && @$item['CollectionType'] !== "livetv")
                    unset($items[$key]);
            }

            $skipDiscover = false;
            if (!empty($excludes) && !$includeHidden)
                if (in_array(md5("_discover"), $excludes))
                    $skipDiscover = true;

            if (!$skipDiscover) {
                if (CatalogsManager::hasCatalogs())
                    $items[-1] = CatalogsManager::getCatalogCollection();
            }

            if (!empty($excludes) && !$includeHidden) {
                foreach ($items as $key => $item) {
                    if (in_array($item['Id'], $excludes))
                        unset($items[$key]);
                }
            }

            if (!empty($order)) {
                $tmpItems = [];
                foreach ($items as $key => $item) {
                    if (in_array($item['Id'], $order)) {
                        $tmpItems[array_search($item['Id'], $order)] = $item;
                    }else{
                        $tmpItems[count($order)+$key] = $item;
                    }
                }
                if (!empty($tmpItems))
                    $items = $tmpItems;
            }

            ksort($items, SORT_ASC);
            $views['Items'] = array_values($items);
            $views['TotalRecordCount'] = count($views['Items']);
            $views['StartIndex'] = 0;
        }

        return $this->response->setBody($views)->setStatus(200)->getResponse();
    }

    public function getUsersItems(string $userId, Request $request) {
        $response = JellyfinManager::getItemsFromSearchTerm($this->response);
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function getUsersItem(string $userId, string $itemId, Request $request) {
        $query = array_merge($request->query(), ['userId' => $userId]);
        $response = JellyfinItem::findById($itemId, $query, $this->response)->getResponse(false, false);
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    //deprecated
    public function getUsersItemsLatest(string $userId, Request $request) {
        return $this->response->make()->getResponse();
    }

    public function getUsersItemPlaybackInfo(string $userId, string $itemId, Request $request) {
        $query = array_merge($request->query(), ['userId' => $userId]);
        JellyfinItem::findById($itemId, $query)->getResponse();
        return $this->response->make()->getResponse();
    }

    public function postUsersItemFavorite(string $userId, string $itemId, Request $request) {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            $item->item_user_id = $userId;
            $item->save();
            Cache::put('item_favorite_'.$itemId, true);
            dispatch(new CommandExecutionJob('library:save-item', ['--itemId' => $itemId]));
            return $this->response->mergeBody(['IsFavorite' => true])->setStatus(200)->getResponse();
        }
        return $this->response->make()->getResponse();
    }

    public function deleteUsersItemFavorite(string $userId, string $itemId, Request $request) {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            Cache::put('item_favorite_'.$itemId, false);
            dispatch(new CommandExecutionJob('library:remove-item', ['--itemId' => $itemId]));
            return $this->response->mergeBody(['IsFavorite' => false])->setStatus(200)->getResponse();
        }
        return $this->response->make()->getResponse();
    }


    /*
     * Plugin Routes
     */
    public function getPlugins(Request $request) {
        $addons = AddonsApiManager::getAddonsFromPlugins();
        return $this->response->make()->mergeBody($addons)->setStatus(200)->getResponse();
    }

    public function getPackages(Request $request) {
        $addons = AddonsApiManager::getAddonsFromPackages();
        return $this->response->make()->mergeBody($addons)->setStatus(200)->getResponse();
    }


    /**
     * Auth Keys
     */

    public function getAuthKeys(Request $request) {
        $response = $this->response->make()->getBody(true);
        $response['Items'] = array_values(array_filter(array_map(function ($item) {
            return $item['AppName'] !== jp_config('app.code_name') ? $item : null;
        }, $response['Items'])));
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function postAuthKeys(Request $request) {
        $response = [];
        if($request->get('App') !== jp_config('app.code_name'))
            $response = $this->response->make()->getBody(true);
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function deleteAuthKey(string $accessToken, Request $request) {
        $response = [];
        if($accessToken !== jp_config('api_key'))
            $response = $this->response->make()->getBody(true);
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    /**
     * Schedule task route
     */

    public function getScheduledTasks(Request $request) {
        $response = $this->response->make()->getBody(true);

        if($request->has('isHidden')) {
            $tasks = TaskManager::getTaskList();
            $response = array_merge($response, array_values($tasks));
        }

        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    //deprecated
    public function getScheduledTask(string $taskId, Request $request) {
        return $this->response->make()->getResponse();
    }

    public function postScheduledTaskRunning(string $taskId, Request $request) {
        $task = new TaskManager($taskId);
        if($task->exists()){
            $response = $task->executeTask();
        }else{
            $response = $this->response->make()->getBody(true);
        }
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    //deprecated
    public function deleteScheduledTaskRunning(string $taskId, Request $request) {
        return $this->response->make()->getResponse();
    }

    /**
     * Other Routes
     */

    public function getWebConfig(Request $request) {
        $response = $this->response->make()->getBody(true);
//        $response['menuLinks'][] = [
//            'name' => 'Discover',
//            'icon' => 'search',
//            'url' => '#/list.html?parentId='.md5('_discover').'&serverId='.jp_config('server_id'),
//        ];
        $response['menuLinks'] = config('jellyfin.menu_links');
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function getWebServerInfo(Request $request) {
        $apiKey = $request->get('apiKey');
        if(isset($apiKey) && $apiKey == jp_config('api_key')){
            return $this->response->setBody($_SERVER)->setStatus(200)
                ->setHeaders(['Content-Type' => 'application/json'])->getResponse();
        }
        return $this->response->setStatus(400)->getResponse();
    }

    public function getSystemInfo(Request $request) {
        return $this->response->make()->getResponse();
    }

    public function getSystemInfoPublic(Request $request) {
        $response = $this->response->make()->getBody(true);
        if(!empty($response)){
            $clientIp = get_client_ip();
            $response['LocalAddress'] = jp_config('jellyfin.external_url');
            $response['ClientIp'] = $clientIp;
            $response['ClientIpLocal'] = local_ip($clientIp);
            if($response['Id'] !== jp_config('server_id'))
                jp_config('server_id', $response['Id']);
        }
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function getSystemConfigurationNetwork(Request $request) {
        $response = $this->response->make()->getBody(true);
        if(!empty($response)){
            $response['InternalHttpPort'] = 8096;
            $response['InternalHttpsPort'] = 8920;
            $response['PublicHttpPort'] = 8096;
            $response['PublicHttpsPort'] = 8920;
        }
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function postSystemConfigurationNetwork(Request $request) {
        $data = $request->all();
        $data['BaseUrl'] = "";
        $data['InternalHttpPort'] = 8096;
        $data['InternalHttpsPort'] = 8920;
        $data['PublicHttpPort'] = 8096;
        $data['PublicHttpsPort'] = 8920;
        return $this->response->setContent($data)->make()->getResponse();
    }

    public function getSystemLogs(Request $request) {
        $logs = $this->response->make()->getBody(true);

        $spLogs = dir_tree(jp_data_path('app/logs'), true);
        foreach($spLogs as $log) {
            $logs[] = [
                'DateCreated' => jellyfin_date(stat($log)['ctime']),
                'DateModified' => jellyfin_date(stat($log)['atime']),
                'Size' => filesize($log),
                'Name' => pathinfo($log, PATHINFO_BASENAME),
            ];
        }

        if(!empty($logs))
            $logs = array_sort($logs, 'DateModified', SORT_DESC);

        return $this->response->setBody($logs)->setStatus(200)->getResponse();
    }

    public function getSystemLog(Request $request) {
        $log = $request->get('name');
        $apiKey = $request->get('api_key');
        if(isset($log) && str_starts_with($log, 'jellyplus')){
            $api = new JellyfinApiManager();
            if($api->testApiKey($apiKey)) {
                $filepath = jp_data_path('app/logs/' . $log);
                if(file_exists($filepath))
                    return response(file_get_contents($filepath), 200)->header('Content-Type', 'text/plain');
            }
        }
        return $this->response->make()->getResponse();
    }

    public function getStartupUser(Request $request) {
        $api = new JellyfinApiManager();
        $info = $api->getSystemInfo();
        $response = $this->response->make()->getBody(true);
        $user = Users::query()->where('user_jellyfin_server_id', $info['Id'])->first();
        if(isset($user)){
            $response = [
                'Name' => $user->user_jellyfin_username,
                'Password' => $user->user_jellyfin_password,
            ];
        }
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function postStartupUser(Request $request) {
        $api = new JellyfinApiManager();
        $info = $api->getSystemInfo();

        $user = Users::query()->where('user_jellyfin_username', $request->get('Name'))
            ->where('user_jellyfin_server_id', $info['Id'])->first();
        if(!isset($user)){
            $user = new Users();
            $user->user_jellyfin_username = $request->get('Name');
            $user->user_jellyfin_server_id = $info['Id'];
        }
        $user->user_jellyfin_password = $request->get('Password');
        $user->save();

        return $this->response->make()->getResponse();
    }

    public function getVirtualFolders(Request $request) {
        $api = new JellyfinApiManager();

        foreach (jp_config('jellyfin.virtual_folders') as $virtualFolder){
            if(!file_exists($virtualFolder['path']))
                mkdir($virtualFolder['path'], 0777, true);

            system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".$virtualFolder['path']);
            $api->createVirtualFolderIfNotExist(t($virtualFolder['name']), $virtualFolder['path'], $virtualFolder['type']);
        }

        system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".jp_data_path('/jellyfin'));

        return $this->response->make()->getResponse();
    }

    //deprecated
    public function postVirtualFolders(Request $request) {
        return $this->response->make()->getResponse();
    }

    public function deleteVirtualFolders(Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->deleteVirtualFolderIfNotPrimary($request->get('name'));
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function getUpdateRequest(string $itemId, Request $request) {
        $apiKey = $request->get('apiKey');
        $api = new JellyfinApiManager();
        if($api->testApiKey($apiKey)) {
            dispatch(new CommandExecutionJob('library:update-item', ['--itemId' => $itemId]));
            return $this->response->setStatus(200)->getResponse();
        }
        return $this->response->setStatus(404)->getResponse();
    }

    public function getDeleteRequest(string $itemId, Request $request) {
        $apiKey = $request->get('apiKey');
        $api = new JellyfinApiManager();
        if($api->testApiKey($apiKey)) {
            dispatch(new CommandExecutionJob('library:remove-item', ['--itemId' => $itemId]));
            return $this->response->setStatus(200)->getResponse();
        }
        return $this->response->setStatus(404)->getResponse();
    }


    /**
     * Configuration Pages
     */

    public function getConfigurationPages(Request $request) {
        $response = $this->response->make()->getBody(true);
        $response[] = [
            "DisplayName" => "Jellyplus",
            "EnableInMainMenu" => true,
            "MenuSection" => "server",
            //"MenuIcon" => "",
            "Name" => "JP_CONF",
            "PluginId" => md5('jellyplus'),
        ];
        return $this->response->setBody($response)->setStatus(200)->getResponse();
    }

    public function getConfigurationPage(Request $request){
        $name = $request->get('name');
        $action = $request->get('action');
        $itemId = $request->get('itemId');
        $jItemId = $request->get('jItemId');
        if($name == "JP_ITEM"){
            $item = Items::query()->where('item_md5', $itemId)->first();
            if(isset($item)) {
                if($action == "download"){
                    $jItem = JellyfinItem::findById($jItemId)->getResponse();
                    if(isset($jItem))
                        return view('download', ['item' => $item, 'jItem' => $jItem]);
                }else{
                    $api = new JellyfinApiManager();
                    $api->setAuthenticationByApiKey();

                    $user = [];
                    if(isset($item->item_user_id))
                        $user = $api->getUser($item->item_user_id);

                    return view('item', ['item' => $item, 'user' => $user]);
                }
            }
        }elseif($name == "JP_CONF"){
            return view('configuration');
        }
        return $this->response->setBody([])->setStatus(404)->getResponse();
    }

    public function getJellyplusConfiguration(Request $request){
        return $this->response->setBody(jp_config())->setStatus(200)->getResponse();
    }

    public function postJellyplusConfiguration(Request $request){
        $api = new JellyfinApiManager();
        $user = $api->getAuthUser();
        if(!empty($user) && (bool) @$user['Policy']['IsAdministrator']){
            foreach ($request->all() as $key => $value) {
                $newMd5 = md5(is_array($value) ? json_encode($value) : $value);
                $oldMd5 = md5(is_array(jp_config($key)) ? json_encode(jp_config($key)) : jp_config($key));
                if($newMd5 !== $oldMd5)
                    jp_config($key, $value);
            }
        }
        return $this->response->setBody(jp_config())->setStatus(200)->getResponse();
    }

    public function getJellyplusItem(Request $request){
        return $this->response->setBody([])->setStatus(200)->getResponse();
    }

    public function postJellyplusItem(Request $request){
        $api = new JellyfinApiManager();
        $user = $api->getAuthUser();
        if(!empty($user) && (bool) @$user['Policy']['IsAdministrator']){
            $data = $request->all();
            if(isset($data['item_md5'])){
                $item = Items::query()->where('item_md5', $data['item_md5'])->first();
                if(isset($item)) {
                    unset($data['item_md5']);
                    foreach ($data as $key => $value) {
                        $item->{$key} = !empty($value) ? $value : null;
                    }
                    $item->save();
                    return $this->response->setBody([])->setStatus(200)->getResponse();
                }
            }
        }
        return $this->response->setBody([])->setStatus(404)->getResponse();
    }

    public function getJellyplusDownload(Request $request){
        return $this->response->setBody([])->setStatus(200)->getResponse();
    }

    public function postJellyplusDownload(Request $request){
        $api = new JellyfinApiManager();
        $user = $api->getAuthUser();
        if(!empty($user) && (bool) @$user['Policy']['IsAdministrator']){
            $data = $request->all();
            if(isset($data['download_url']) && isset($data['download_path'])) {
                $downloadUrl = $data['download_url'] . '&mfp=0&download=1';
                $params = [
                    '--url' => $downloadUrl,
                    '--path' => $data['download_path'],
                    '--filename' => $data['download_filename'],
                ];
                dispatch(new CommandExecutionJob('library:download-stream', $params));
                return $this->response->setBody([])->setStatus(200)->getResponse();
            }
        }
        return $this->response->setBody([])->setStatus(404)->getResponse();
    }
}
