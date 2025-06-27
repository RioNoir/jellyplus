<!DOCTYPE html>
<html>
<head>
    <title>Jellyplus</title>
</head>
<body>
<div id="configPage" data-role="page" class="page type-interior pluginConfigurationPage configPage" data-require="emby-input,emby-button,emby-checkbox,emby-linkbutton,emby-textarea">
    <div data-role="content">
        <div class="content-primary">
            <h1>Jellyplus Configuration</h1>
            <form id="configForm" class="configForm">

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>Base Configuration</h3></legend>
                    <div class="inputContainer">
                        <input is="emby-input" name="external_url" type="text" label="Server URL" value="{{empty(jp_config('external_url')) ? app_url() : jp_config('external_url')}}"/>
                    </div>
                    <div class="fieldDescription" style="margin-bottom: 20px">
                        It is not possible to change these parameters, the system will update them automatically if needed.
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" readonly type="text" label="Server ID" value="{{jp_config('server_id')}}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" readonly type="text" label="Api Key" value="{{jp_config('api_key')}}"/>
                    </div>
                </fieldset>

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>Addons Configuration</h3></legend>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="addons.timeout" label="Timeout" value="{{jp_config('addons.timeout')}}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="addons.connect_timeout" label="Connect Timeout" value="{{jp_config('addons.connect_timeout')}}"/>
                    </div>
                    <div class="inputContainer">
                        <div class="selectContainer" style="margin-bottom: 2px">
                            <select is="emby-select" name="addons.search_disabled" label="Disable Addons on Global Search">
                                <option value="0" @if((int) jp_config('addons.search_disabled') == 0) selected @endif>No</option>
                                <option value="1" @if((int) jp_config('addons.search_disabled') == 1) selected @endif>Yes</option>
                            </select>
                        </div>
                        <div class="fieldDescription">When you enable this option, only results from IMDB or TMDB (if enabled) will appear in the global search.</div>
                    </div>
                    <div class="inputContainer">
                        <select multiple is="emby-select" name="addons.disabled" label="Disable Addons" style="height: auto;">
                            @foreach(jp_config('addons.loaded') as $id => $name)
                                <option value="{{$id}}" @if(in_array($id, @jp_config('addons.disabled') ?? [])) selected @endif>
                                    {{$name}} @if(in_array($id, @jp_config('addons.disabled') ?? [])) [selected] @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="inputContainer">
                        <select multiple is="emby-select" name="addons.excluded_discover" label="Exclude Addons from Discover" style="height: auto;">
                            @foreach(jp_config('addons.loaded') as $id => $name)
                                <option value="{{$id}}" @if(in_array($id, @jp_config('addons.excluded_discover') ?? [])) selected @endif>
                                    {{$name}} @if(in_array($id, @jp_config('addons.excluded_discover') ?? [])) [selected] @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="inputContainer">
                        <select multiple is="emby-select" name="addons.excluded_search" label="Exclude Addons from Global search" style="height: auto;">
                            @foreach(jp_config('addons.loaded') as $id => $name)
                                <option value="{{$id}}" @if(in_array($id, @jp_config('addons.excluded_search') ?? [])) selected @endif>
                                    {{$name}} @if(in_array($id, @jp_config('addons.excluded_search') ?? [])) [selected] @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="inputContainer">
                        <select multiple is="emby-select" name="addons.excluded_stream" label="Exclude Addons from Stream search" style="height: auto;">
                            @foreach(jp_config('addons.loaded') as $id => $name)
                                <option value="{{$id}}" @if(in_array($id, @jp_config('addons.excluded_stream') ?? [])) selected @endif>
                                    {{$name}} @if(in_array($id, @jp_config('addons.excluded_stream') ?? [])) [selected] @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </fieldset>

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>Streams Configuration</h3></legend>
                    <div class="inputContainer">
                        <input is="emby-input" type="number" name="stream.cache_ttl" pattern="[0-9]*"
                               required min="0" max="1000" label="Cache TTL (Minutes)"
                               value="{{jp_config('stream.cache_ttl')}}"
                        />
                        <div class="fieldDescription">Number in Minutes for which the system should cache streams.</div>
                    </div>
                    <div class="inputContainer">
                        <div class="selectContainer" style="margin-bottom: 2px">
                            <select is="emby-select" name="stream.resolution" label="Default Resolution">
                                @foreach(jp_config('stream.resolutions') as $option)
                                    <option value="{{$option}}" @if($option == jp_config('stream.resolution')) selected @endif>{{$option}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fieldDescription">Stream resolution by default, is used in the Auto stream function and in third-party clients where streams cannot be selected. If it is not available it takes the first available option after it.</div>
                    </div>
                    <div class="inputContainer">
                        <div class="selectContainer" style="margin-bottom: 2px">
                            <select is="emby-select" name="stream.format" label="Default Format">
                                @foreach(jp_config('stream.formats') as $option)
                                    <option value="{{$option}}" @if($option == jp_config('stream.format')) selected @endif>{{$option}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fieldDescription">Stream format by default, is used in the Auto stream function and in third-party clients where streams cannot be selected. If it is not available it takes the first available option after it.</div>
                    </div>
                    <div class="inputContainer">
{{--                        <input is="emby-input" type="text" name="stream.lang" required label="Default Language"--}}
{{--                               value="{{jp_config('stream.lang')}}"--}}
{{--                        />--}}
                        <div class="selectContainer" style="margin-bottom: 2px">
                            <select is="emby-select" name="stream.lang" label="Default Language">
                                @foreach(\App\Services\Helpers\LangHelper::getFullLanguageList() as $key => $value)
                                    <option value="{{$key}}" @if($key == jp_config('stream.lang')) selected @endif>{{$value}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fieldDescription">Stream language by default, is used in the Auto stream function and in third-party clients where streams cannot be selected.
                            It can be configured at the User level through the option <b>Preferred Audio Language</b> found <a href="/web/#/mypreferencesplayback.html" target="_blank">here</a>.</div>
                    </div>
                    <div class="inputContainer">
                        <div class="selectContainer" style="margin-bottom: 2px">
                            <select is="emby-select" name="stream.only_language_match" label="Include only language matches">
                                <option value="0" @if((int) jp_config('stream.only_language_match') == 0) selected @endif>No</option>
                                <option value="1" @if((int) jp_config('stream.only_language_match') == 1) selected @endif>Yes</option>
                            </select>
                        </div>
                        <div class="fieldDescription">The system will exclude all sources that do not contain the language entered above. It may not work properly in some cases.</div>
                    </div>
                    <div class="inputContainer">
                        <div class="selectContainer" style="margin-bottom: 2px">
                            <select is="emby-select" name="stream.exclude_torrent_sources" label="Exclude Torrent sources">
                                <option value="0" @if((int) jp_config('stream.exclude_torrent_sources') == 0) selected @endif>No</option>
                                <option value="1" @if((int) jp_config('stream.exclude_torrent_sources') == 1) selected @endif>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="inputContainer">
                        <label class="textareaLabel" for="txtLoginDisclaimer">Sort By Keywords</label>
                        <textarea is="emby-textarea" name="stream.sortby_keywords" label="Included Keywords" class="textarea-mono emby-textarea" rows="1" style="height: 100px; width: 100%">{!! trim(implode("\n", jp_config('stream.sortby_keywords'))) !!}</textarea>
                        <div class="fieldDescription">Keywords for sorting streams, line-break separated.</div>
                    </div>
                    <div class="inputContainer">
                        <label class="textareaLabel" for="txtLoginDisclaimer">Included Keywords</label>
                        <textarea is="emby-textarea" name="stream.included_keywords" label="Included Keywords" class="textarea-mono emby-textarea" rows="1" style="height: 100px; width: 100%">{!! trim(implode("\n", jp_config('stream.included_keywords'))) !!}</textarea>
                        <div class="fieldDescription">Keywords to be include from streams, line-break separated.</div>
                    </div>
                    <div class="inputContainer">
                        <label class="textareaLabel" for="txtLoginDisclaimer">Excluded Keywords</label>
                        <textarea is="emby-textarea" name="stream.excluded_keywords" label="Excluded Keywords" class="textarea-mono emby-textarea" rows="1" style="height: 100px; width: 100%">{!! trim(implode("\n", jp_config('stream.excluded_keywords'))) !!}</textarea>
                        <div class="fieldDescription">Keywords to be exclude from streams, line-break separated.</div>
                    </div>
                    <div class="inputContainer">
                        <label class="textareaLabel" for="txtLoginDisclaimer">Excluded Formats</label>
                        <textarea is="emby-textarea" name="stream.excluded_formats" label="Excluded Formats" class="textarea-mono emby-textarea" rows="1" style="height: 100px; width: 100%">{!! trim(implode("\n", jp_config('stream.excluded_formats'))) !!}</textarea>
                        <div class="fieldDescription">Formats to be exclude from streams, line-break separated.</div>
                    </div>
                    <div class="inputContainer">
                        <label class="textareaLabel" for="txtLoginDisclaimer">Excluded Paths</label>
                        <textarea is="emby-textarea" name="stream.excluded_paths" label="Excluded Paths" class="textarea-mono emby-textarea" rows="1" style="height: 100px; width: 100%">{!! trim(implode("\n", jp_config('stream.excluded_paths'))) !!}</textarea>
                        <div class="fieldDescription">Path to be exclude from stream urls, line-break separated.</div>
                    </div>
                </fieldset>

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>TMDB Configuration</h3></legend>
                    <div class="fieldDescription" style="margin-bottom: 20px">
                        Use the TMDB api for searches and items detail. View the official documentation
                        <a is="emby-linkbutton" rel="noopener noreferrer" class="button-link emby-button" href="https://developer.themoviedb.org/docs/getting-started" target="_blank">here</a>.
                    </div>
                    <label class="checkboxContainer">
                        <input is="emby-checkbox" type="checkbox" name="tmdb.enabled" @if(jp_config('tmdb.enabled')) checked @endif/>
                        <span>Enabled</span>
                    </label>
                    <div class="inputContainer">
                        <input is="emby-input" type="password" name="tmdb.api_key" label="API Key" value="{{jp_config('tmdb.api_key')}}"/>
                    </div>
                    <div class="selectContainer">
                        <select is="emby-select" name="tmdb.api_language" label="API Language">
                            @foreach(\App\Services\Helpers\LangHelper::getFullLanguageList(false) as $key => $value)
                                <option value="{{$key}}" @if($key == jp_config('tmdb.api_language')) selected @endif>{{$value}}</option>
                            @endforeach
                        </select>
                    </div>
{{--                    <div class="inputContainer">--}}
{{--                        <input is="emby-input" type="text" name="tmdb.api_language" label="API Language" value="{{jp_config('tmdb.api_language') ?? "en"}}"/>--}}
{{--                    </div>--}}
                </fieldset>

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>MediaFlowProxy Configuration</h3></legend>
                    <div class="fieldDescription" style="margin-bottom: 20px">
                        Proxy Video streams. The system has MediaFlowProxy installed by default, this allows streams to be sent to clients through it. View official documentation
                        <a is="emby-linkbutton" rel="noopener noreferrer" class="button-link emby-button" href="https://github.com/mhdzumair/mediaflow-proxy" target="_blank">here</a>.
                    </div>
                    <label class="checkboxContainer" style="margin-bottom: 1px">
                        <input is="emby-checkbox" type="checkbox" id="mediaflowproxy.enabled" name="mediaflowproxy.enabled" @if(jp_config('mediaflowproxy.enabled')) checked @endif/>
                        <span>Enabled</span>
                    </label>
                    <div class="fieldDescription" style="margin-bottom: 20px">When this is enabled, the system uses MediaFlowProxy to send streams to clients, is avoided if the client's ip address matches that of the server.</div>
                    <label class="checkboxContainer" style="margin-bottom: 1px">
                        <input is="emby-checkbox" type="checkbox" id="mediaflowproxy.enabled_external" name="mediaflowproxy.enabled_external" @if(jp_config('mediaflowproxy.enabled_external')) checked @endif/>
                        <span>Enable custom server</span>
                    </label>
                    <div class="fieldDescription" style="margin-bottom: 20px">Enable to use the server and credentials entered below instead of the built-in version.</div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="mediaflowproxy.url" label="URL" value="{{jp_config('mediaflowproxy.url')}}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="password" name="mediaflowproxy.api_password" label="API Password" value="{{jp_config('mediaflowproxy.api_password')}}"/>
                    </div>
                    <div class="inputContainer">
                        <label class="textareaLabel" for="txtLoginDisclaimer">Excluded Domains</label>
                        <textarea is="emby-textarea" name="mediaflowproxy.excluded_domains" label="Excluded Domains" class="textarea-mono emby-textarea" rows="1" style="height: 100px; width: 100%">{!! !empty(jp_config('mediaflowproxy.excluded_domains')) ? trim(implode("\n", jp_config('mediaflowproxy.excluded_domains'))) : "" !!}</textarea>
                        <div class="fieldDescription">Domains excluded from stream proxy, line-break separated.</div>
                    </div>
                </fieldset>

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>HTTP Proxy Configuration</h3></legend>
                    <div class="fieldDescription" style="margin-bottom: 20px">
                        HTTP Proxy for external API calls (like Addons calls).
                    </div>
                    <label class="checkboxContainer">
                        <input is="emby-checkbox" type="checkbox" name="http_proxy.enabled" @if(jp_config('http_proxy.enabled')) checked @endif/>
                        <span>Enabled</span>
                    </label>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="http_proxy.host" label="Host" value="{{jp_config('http_proxy.host')}}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="http_proxy.port" label="Port" value="{{jp_config('http_proxy.port')}}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="http_proxy.username" label="Username" value="{{jp_config('http_proxy.username')}}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="password" name="http_proxy.password" label="Password" value="{{jp_config('http_proxy.password')}}"/>
                    </div>
                    <div class="inputContainer">
                        <label class="textareaLabel" for="txtLoginDisclaimer">Excluded Domains</label>
                        <textarea is="emby-textarea" name="http_proxy.excluded_domains" label="Excluded Domains" class="textarea-mono emby-textarea" rows="1" style="height: 100px; width: 100%">{!! !empty(jp_config('http_proxy.excluded_domains')) ? trim(implode("\n", jp_config('http_proxy.excluded_domains'))) : "" !!}</textarea>
                        <div class="fieldDescription">Domains excluded from http proxy, line-break separated.</div>
                    </div>
                </fieldset>

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>Jobs Configuration</h3></legend>
                    <div class="inputContainer">
                        <input is="emby-input" type="number" name="jellyfin.delete_unused_items_after" pattern="[0-9]*"
                               required min="0" max="1000" label="Delete unused items after (Days)"
                               value="{{jp_config('jellyfin.delete_unused_items_after')}}"
                        />
                        <div class="fieldDescription">Number in Days after which the system should delete Items that were added to the library but were never opened.</div>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="number" name="jellyfin.update_series_after" pattern="[0-9]*"
                               required min="0" max="1000" label="Update series after (Hours)"
                               value="{{jp_config('jellyfin.update_series_after')}}"
                        />
                        <div class="fieldDescription">Number in Hours after which the system should check for updates of TV series.</div>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="number" name="jellyfin.delete_streams_after" pattern="[0-9]*"
                               required min="0" max="1000" label="Delete streams after (Hours)"
                               value="{{jp_config('jellyfin.delete_streams_after')}}"
                        />
                        <div class="fieldDescription">Number in Hours after which the system should delete the Streams of Items it has cached.</div>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="number" name="jellyfin.update_playback_limit" pattern="[0-9]*"
                               required min="0" max="1000" label="Limit of Playback Info Items"
                               value="{{jp_config('jellyfin.update_playback_limit')}}"
                        />
                        <div class="fieldDescription">Number of items that need to be updated during the PlayBack Info task.</div>
                    </div>
                </fieldset>

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>Tasks Configuration</h3></legend>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="tasks.cron.{{md5('library:clean')}}"
                               required label="Library Clean Cronjob"
                               value="{{jp_config('tasks.cron.'.md5('library:clean'))}}"
                        />
                        <div class="fieldDescription">Cleans library, deletes old streams</div>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="tasks.cron.{{md5('library:update')}}"
                               required label="Library Update Cronjob"
                               value="{{jp_config('tasks.cron.'.md5('library:update'))}}"
                        />
                        <div class="fieldDescription">Updates TV series episodes of the library</div>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="tasks.cron.{{md5('library:rebuild')}}"
                               required label="Library Rebuild Cronjob"
                               value="{{jp_config('tasks.cron.'.md5('library:rebuild'))}}"
                        />
                        <div class="fieldDescription">Rebuild library, recreates .nfo and .strm files</div>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="tasks.cron.{{md5('library:playback-info')}}"
                               label="Library PlayBack-Info Cronjob"
                               value="{{jp_config('tasks.cron.'.md5('library:playback-info'))}}"
                        />
                        <div class="fieldDescription">Gets the Playback Info of the items in the library, disabled by default because it makes many requests to addons, enable discreetly</div>
                    </div>
                </fieldset>

                <div style="margin-top: 5px">
                    <button is="emby-button" type="submit" class="raised button-submit block"><span>Save</span></button>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript">
        document.querySelector('.configForm')
            .addEventListener('submit', function (e) {
                let PluginId = "jellyplus";

                Dashboard.showLoadingMsg();
                ApiClient.getPluginConfiguration(PluginId).then(function (config) {
                    const form = document.getElementById("configForm");
                    const formData = new FormData(form);
                    //console.log(formData);
                    const values = {};
                    formData.forEach((value, key) => {
                        values[key] = value;
                    });
                    form.querySelectorAll('input[type="number"]').forEach((number) => {
                        values[number.name] = parseInt(number.value);
                    });
                    form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                        values[checkbox.name] = checkbox.checked;
                    });
                    form.querySelectorAll('textarea').forEach((textarea) => {
                        values[textarea.name] = textarea.value.split("\n").map(riga => riga.trim()).filter(riga => riga !== "");
                    });
                    form.querySelectorAll('select[multiple]').forEach((select) => {
                        let options = select.selectedOptions;
                        values[select.name] = Array.from(options).map(({ value }) => value);
                    });

                    console.log(values);

                    ApiClient.updatePluginConfiguration(PluginId, values).then(Dashboard.processPluginConfigurationUpdateResult);
                });

                e.preventDefault();
                return false;
            });
    </script>
</div>
</body>
</html>
