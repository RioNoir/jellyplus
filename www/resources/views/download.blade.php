<!DOCTYPE html>
<html>
<head>
    <title>Jellyplus</title>
</head>
<body>
<div id="configPage" data-role="page" class="page type-interior pluginConfigurationPage configPage" data-require="emby-input,emby-button,emby-checkbox,emby-linkbutton,emby-textarea">
    <div data-role="content">
        <div class="content-primary">
            <h1>Download Jellyplus Item "{{ $item->item_title }}"
                @if(isset($jItem['ParentIndexNumber']) && isset($jItem['IndexNumber']))
                    S{{$jItem['ParentIndexNumber']}}E{{$jItem['IndexNumber']}}
                @endif
            </h1>
            @if(@$jItem['Type'] == 'Episode')
                <h3>"{{ @$jItem['Name'] }}"</h3>
            @endif

            @if(!empty(@$jItem['MediaSources']))
                <form id="configForm" class="configForm">

                <fieldset class="verticalSection">
                    <legend><h3>Download on Client</h3></legend>
                    <div class="inputContainer">
                        <select is="emby-select" id="download-url" label="Choose version" style="height: auto;">
                            @foreach($jItem['MediaSources'] as $key => $mediaSource)
                                @if(@$mediaSource['SourceType'] == "ExternalStream")
                                    <option value="{{$mediaSource['Path']}}">
                                        {{ $mediaSource['Name'] }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-top: 5px">
                        <button is="emby-button" id="download-item" type="button" class="raised button-submit block" style="background-color: darkgreen;"><span>Download</span></button>
                    </div>
                </fieldset>

                <fieldset class="verticalSection">
                    <legend><h3>Download on Server</h3></legend>
                    <div class="inputContainer">
                        <select is="emby-select" name="download_url" label="Choose version" style="height: auto;">
                            @foreach($jItem['MediaSources'] as $key => $mediaSource)
                                @if(@$mediaSource['SourceType'] == "ExternalStream")
                                    <option value="{{$mediaSource['Path']}}">
                                        {{ $mediaSource['Name'] }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" name="download_path" type="text" label="Download Path" value="{{ pathinfo($jItem['Path'], PATHINFO_DIRNAME) }}"/>
                    </div>
                    <div class="inputContainer">
                        <input is="emby-input" name="download_filename" label="Download File Name (without Extension)" value="{{ pathinfo($jItem['Path'], PATHINFO_FILENAME) }}"/>
                    </div>
                    <div style="margin-top: 25px">
                        <button is="emby-button" type="submit" class="raised button-submit block" style="background-color: darkorange;"><span>Download</span></button>
                    </div>
                </fieldset>
            </form>
            @endif
        </div>
    </div>

    <script type="text/javascript">

        document.querySelector('#download-item').addEventListener('click', function (e) {
            let url =  document.getElementById('download-url').value;
            const link = document.createElement("a");
            link.href = url+"&mfp=0&download=1";
            link.click();
        });

        document.querySelector('.configForm')
            .addEventListener('submit', function (e) {
                let PluginId = "sp-download";

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

                alert('Download command sent! If the file will not download check the logs.');

                e.preventDefault();
                return false;
            });
    </script>
</div>
</body>
</html>
