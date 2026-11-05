<?php

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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'https://labellenailsbog.com,http://localhost:4200,http://127.0.0.1:4200,http://localhost:8000,http://127.0.0.1:8000')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['X-API-Key', 'Authorization', 'Content-Type', 'X-Requested-With', 'Accept', 'X-Confirmation-Token'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
