<?php

$allowed_origins = [];
$allowed_origins[] = env('APP_FRONTEND_ORIGIN');

$APP_CORS_ORIGINS = env('APP_CORS_ORIGINS');
if (isset($APP_CORS_ORIGINS)) {
    foreach (explode(',', $APP_CORS_ORIGINS) as $origin) {
        if ($origin === '*') {
            $allowed_origins = ['*'];
            break;
        }
        $allowed_origins[] = $origin;
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => $allowed_origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
