<!DOCTYPE html>
<html>
<head>
    <title>Jellyplus</title>
</head>
<body>
<div id="configPage" data-role="page" class="page type-interior pluginConfigurationPage configPage" data-require="emby-input,emby-button,emby-checkbox,emby-linkbutton,emby-textarea">
    <div data-role="content">
        <div class="content-primary">
            <h1>Edit Jellyplus Item "{{ $item->item_title }}"</h1>
            <form id="configForm" class="configForm">

                <fieldset class="verticalSection">
                    <legend><h3>Actions</h3></legend>
                    <div style="margin-top: 5px; @if($item->item_type == "tvSeries") display:block; @else display:none; @endif">
                        <button is="emby-button" id="update-item" type="button" class="raised button-submit block" style="background-color: darkgreen;"><span>Update Item</span></button>
                    </div>
                    <div style="margin-top: 5px">
                        <button is="emby-button" id="delete-item" type="button" class="raised button-submit block" style="background-color: darkred;"><span>Delete Item</span></button>
                    </div>
                </fieldset>

                <fieldset class="verticalSection">
                    <legend><h3>Item Info</h3></legend>
                    <div class="fieldDescription" style="margin-bottom: 20px">
                        It is not possible to change these parameters.
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" readonly name="item_md5" type="text" label="Item ID" value="{{ $item->item_md5 }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" readonly type="text" label="Item Type" value="{{ $item->item_type }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" readonly type="text" label="Created By" value="{{ @$user['Name'] ?? "Unknown" }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" readonly type="text" label="Created At" value="{{ $item->created_at }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" readonly type="text" label="Updated At" value="{{ $item->updated_at }}"/>
                    </div>
                </fieldset>

                <fieldset class="verticalSection">
                    <legend><h3>Base Configuration</h3></legend>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_path" label="Path" placeholder="library/movies/tt0439935" value="{{ $item->item_path }}"/>
                        <div class="fieldDescription">The path must start from the library folder, please change only if necessary.</div>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_title" label="Title" value="{{ $item->item_title }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_original_title" label="Original Title" value="{{ $item->item_original_title }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_description" label="Description" value="{{ $item->item_description }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_year" label="Production Year" value="{{ $item->item_year }}"/>
                    </div>
                </fieldset>

                <fieldset class="verticalSection verticalSection-extrabottompadding">
                    <legend><h3>External Ids Configuration</h3></legend>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_imdb_id" label="IMDB ID" value="{{ $item->item_imdb_id }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_tmdb_id" label="TMDB ID" value="{{ $item->item_tmdb_id }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_jellyfin_id" label="Jellyfin ID" value="{{ $item->item_jellyfin_id }}"/>
                    </div>
                    <div class="fieldDescription" style="margin-bottom: 20px">
                        The following fields are filled in only if the item was inserted through an Addon.
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_addon_id" label="Addon ID" value="{{ $item->item_addon_id }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" type="text" name="item_addon_meta_id" label="Addon Meta ID" value="{{ $item->item_addon_meta_id }}"/>
                    </div>
                </fieldset>

                <div style="margin-top: 5px">
                    <button is="emby-button" type="submit" class="raised button-submit block"><span>Save</span></button>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript">

        document.querySelector('#update-item').addEventListener('click', function (e) {
            fetch("{{app_url('/Items/'.$item->item_md5.'/UpdateRequest?apiKey='.jp_config('api_key'))}}").then(function(response) {
                return response.json();
            }).then(function(data) {
                console.log(data);
            }).catch(function(err) {
                console.log('Fetch Error :-S', err);
            });
            alert('Update request sent!');
        });

        document.querySelector('#delete-item').addEventListener('click', function (e) {
            if(confirm('Do you want to delete this item?')){
                fetch("{{app_url('/Items/'.$item->item_md5.'/DeleteRequest?apiKey='.jp_config('api_key'))}}").then(function(response) {
                    return response.json();
                }).then(function(data) {
                    console.log(data);
                }).catch(function(err) {
                    console.log('Fetch Error :-S', err);
                });
                alert('Delete request sent!');
            }
        });

        document.querySelector('.configForm')
            .addEventListener('submit', function (e) {
                let PluginId = "jellyplus-item";

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

                    ApiClient.updatePluginConfiguration(PluginId, values).then(Dashboard.processPluginConfigurationUpdateResult);
                });

                e.preventDefault();
                return false;
            });
    </script>
</div>
</body>
</html>
