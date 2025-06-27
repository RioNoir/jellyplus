<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/stream', ['as'=> 'stream', 'uses'=>'StreamController@getStream']);

//Jellyfin Proxed Routes
$router->get('/web/config.json',                                ['as'=> 'jellyfin.config', 'uses'=>'JellyfinController@getWebConfig']);
$router->get('/web/server.json',                                ['as'=> 'jellyfin.server', 'uses'=>'JellyfinController@getWebServerInfo']);
$router->get('/web/configurationpages',                         ['as'=> 'jellyfin.configpages', 'uses'=>'JellyfinController@getConfigurationPages']);
$router->get('/web/ConfigurationPages',                         ['as'=> 'jellyfin.configpages', 'uses'=>'JellyfinController@getConfigurationPages']);
$router->get('/web/configurationpage',                          ['as'=> 'jellyfin.configpage', 'uses'=>'JellyfinController@getConfigurationPage']);

$router->get('/Plugins/jellyplus/Configuration',                ['as'=> 'jellyplus.config.get', 'uses'=>'JellyfinController@getSPConfiguration']);
$router->post('/Plugins/jellyplus/Configuration',               ['as'=> 'jellyplus.config.update', 'uses'=>'JellyfinController@postSPConfiguration']);
$router->get('/Plugins/jellyplus-item/Configuration',           ['as'=> 'jellyplus.item.get', 'uses'=>'JellyfinController@getSPItem']);
$router->post('/Plugins/jellyplus-item/Configuration',          ['as'=> 'jellyplus.item.update', 'uses'=>'JellyfinController@postSPItem']);
$router->get('/Plugins/jellyplus-download/Configuration',       ['as'=> 'jellyplus.download.get', 'uses'=>'JellyfinController@getSPDownload']);
$router->post('/Plugins/jellyplus-download/Configuration',      ['as'=> 'jellyplus.download.post', 'uses'=>'JellyfinController@postSPDownload']);

$router->get('/System/Info',                                    ['as'=> 'jellyfin.system.info', 'uses'=>'JellyfinController@getSystemInfoPublic']);
$router->get('/System/Info/Public',                             ['as'=> 'jellyfin.system.info.public', 'uses'=>'JellyfinController@getSystemInfoPublic']);
$router->get('/system/info/public',                             ['as'=> 'jellyfin.system.info.public2', 'uses'=>'JellyfinController@getSystemInfoPublic']);

$router->get('/System/Logs',                                    ['as'=> 'jellyfin.system.logs', 'uses'=>'JellyfinController@getSystemLogs']);
$router->get('/System/Logs/Log',                                ['as'=> 'jellyfin.system.log', 'uses'=>'JellyfinController@getSystemLog']);

$router->get('/System/Configuration/network',                   ['as'=> 'jellyfin.system.configuration.network', 'uses'=>'JellyfinController@getSystemConfigurationNetwork']);
$router->post('/System/Configuration/network',                  ['as'=> 'jellyfin.system.configuration.network.post', 'uses'=>'JellyfinController@postSystemConfigurationNetwork']);

$router->get('/Startup/User',                                   ['as'=> 'jellyfin.startup_user', 'uses'=>'JellyfinController@getStartupUser']);
$router->post('/Startup/User',                                  ['as'=> 'jellyfin.startup_user', 'uses'=>'JellyfinController@postStartupUser']);

$router->get('/Library/VirtualFolders',                         ['as'=> 'jellyfin.virtual_folders', 'uses'=>'JellyfinController@getVirtualFolders']);
//$router->post('/Library/VirtualFolders',                        ['as'=> 'jellyfin.virtual_folders.create', 'uses'=>'JellyfinController@postVirtualFolders']);
$router->delete('/Library/VirtualFolders',                      ['as'=> 'jellyfin.virtual_folders.delete', 'uses'=>'JellyfinController@deleteVirtualFolders']);

$router->get('/Items',                                          ['as'=> 'jellyfin.items', 'uses'=>'JellyfinController@getItems']);
$router->get('/Items/{itemId}',                                 ['as'=> 'jellyfin.items.detail', 'uses'=>'JellyfinController@getItem']);
$router->post('/Items/{itemId}',                                ['as'=> 'jellyfin.items.post', 'uses'=>'JellyfinController@postItem']);
$router->delete('/Items/{itemId}',                              ['as'=> 'jellyfin.items.delete', 'uses'=>'JellyfinController@deleteItem']);
$router->get('/Items/{itemId}/Download',                        ['as'=> 'jellyfin.items.download', 'uses'=>'JellyfinController@getItemsDownload']);
$router->get('/Items/{itemId}/ThemeMedia',                      ['as'=> 'jellyfin.items.theme_media', 'uses'=>'JellyfinController@getItemsThemeMedia']);
$router->get('/Items/{itemId}/Similar',                         ['as'=> 'jellyfin.items.similar', 'uses'=>'JellyfinController@getItemsSimilar']);
$router->get('/Items/{itemId}/PlaybackInfo',                    ['as'=> 'jellyfin.items.playback_info', 'uses'=>'JellyfinController@getItemsPlaybackInfo']);
$router->post('/Items/{itemId}/PlaybackInfo',                   ['as'=> 'jellyfin.items.playback_info.post', 'uses'=>'JellyfinController@postItemsPlaybackInfo']);
$router->get('/Items/{itemId}/MetadataEditor',                  ['as'=> 'jellyfin.items.metadata_editor', 'uses'=>'JellyfinController@getMetadataEditor']);
$router->get('/Items/{itemId}/Images/{imageId}',                ['as'=> 'jellyfin.items.images', 'uses'=>'JellyfinController@getItemsImages']);
//$router->post('/Items/{itemId}/Images/{imageId}',               ['as'=> 'jellyfin.items.images.post', 'uses'=>'JellyfinController@postItemsImages']);
//$router->delete('/Items/{itemId}/Images/{imageId}',             ['as'=> 'jellyfin.items.images.delete', 'uses'=>'JellyfinController@deleteItemsImages']);

//Custom for SP
$router->get('/Items/{itemId}/UpdateRequest',                   ['as'=> 'jellyfin.items.update_request', 'uses'=>'JellyfinController@getUpdateRequest']);
$router->get('/Items/{itemId}/DeleteRequest',                   ['as'=> 'jellyfin.items.delete_request', 'uses'=>'JellyfinController@getDeleteRequest']);

$router->get('/UserViews',                                      ['as'=> 'jellyfin.userviews', 'uses'=>'JellyfinController@getUserViews']);
$router->get('/Users/{userId}/Views',                           ['as'=> 'jellyfin.users.views', 'uses'=>'JellyfinController@getUserViews']);
$router->get('/Users/{userId}/Items',                           ['as'=> 'jellyfin.users.items', 'uses'=>'JellyfinController@getUsersItems']);
$router->get('/Users/{userId}/Items/Latest',                    ['as'=> 'jellyfin.users.items.latest', 'uses'=>'JellyfinController@getUsersItemsLatest']);
$router->get('/Users/{userId}/Items/{itemId}',                  ['as'=> 'jellyfin.users.item', 'uses'=>'JellyfinController@getUsersItem']);
$router->get('/Users/{userId}/Items/{itemId}/PlaybackInfo',     ['as'=> 'jellyfin.users.item.playback_info', 'uses'=>'JellyfinController@getUsersItemPlaybackInfo']);
$router->post('/Users/{userId}/FavoriteItems/{itemId}',         ['as'=> 'jellyfin.users.item.favorite', 'uses'=>'JellyfinController@postUsersItemFavorite']);
$router->delete('/Users/{userId}/FavoriteItems/{itemId}',       ['as'=> 'jellyfin.users.item.favorite.delete', 'uses'=>'JellyfinController@deleteUsersItemFavorite']);

$router->get('/Videos/{itemId}/stream',                         ['as'=> 'jellyfin.videos.stream', 'uses'=>'JellyfinController@getVideosStream']);

$router->get('/Auth/Keys',                                      ['as'=> 'jellyfin.auth.keys', 'uses'=>'JellyfinController@getAuthKeys']);
$router->post('/Auth/Keys',                                     ['as'=> 'jellyfin.auth.keys.post', 'uses'=>'JellyfinController@postAuthKeys']);
$router->delete('/Auth/Keys/{accessToken}',                     ['as'=> 'jellyfin.auth.keys.delete', 'uses'=>'JellyfinController@deleteAuthKey']);

$router->get('/ScheduledTasks',                                 ['as'=> 'jellyfin.schedule_tasks', 'uses'=>'JellyfinController@getScheduledTasks']);
//$router->get('/ScheduledTasks/{taskId}',                        ['as'=> 'jellyfin.schedule_task', 'uses'=>'JellyfinController@getScheduledTask']);
$router->post('/ScheduledTasks/Running/{taskId}',               ['as'=> 'jellyfin.schedule_task.running', 'uses'=>'JellyfinController@postScheduledTaskRunning']);
//$router->delete('/ScheduledTasks/Running/{taskId}',             ['as'=> 'jellyfin.schedule_task.running.delete', 'uses'=>'JellyfinController@deleteScheduledTaskRunning']);

//$router->get('/Persons',                                        ['as'=> 'jellyfin.persons', 'uses'=>'JellyfinController@getPersons']);
//$router->get('/Artists',                                        ['as'=> 'jellyfin.artists', 'uses'=>'JellyfinController@getArtists']);
$router->get('/Plugins',                                        ['as'=> 'jellyfin.plugins', 'uses'=>'JellyfinController@getPlugins']);
$router->get('/Packages',                                       ['as'=> 'jellyfin.packages', 'uses'=>'JellyfinController@getPackages']);
