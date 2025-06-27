<?php

use Illuminate\Support\Str;

return [
    'driver' => 'file',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => jp_data_path('app/sessions'),
    'connection' => null,
    'table' => 'sessions',
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => 'jp_session',
    'path' => '/',
    'domain' => null,
    'secure' => null,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
