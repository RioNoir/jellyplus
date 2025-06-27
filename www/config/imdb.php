<?php

return [
    'url' => env('IMDB_URL', 'https://www.imdb.com'),
    'api_url' => env('IMDB_API_URL', 'https://www.imdb.com/_next/data/{api_key}/en-US/title'),
    'suggestions_url' => env('IMDB_SUGGESTIONS_URL', 'https://v3.sg.media-imdb.com/suggestion/x/{search_term}.json'),
];
