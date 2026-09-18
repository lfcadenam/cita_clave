<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bold Colombia Configuration
    |--------------------------------------------------------------------------
    */

    'api_key' => env('BOLD_API_KEY', 'x_test_bold_api_key_sample'),
    'secret_key' => env('BOLD_SECRET_KEY', 's_test_bold_secret_sample'),
    'integrity_key' => env('BOLD_INTEGRITY_KEY', 'test_bold_integrity_secret_key_12345'),
    'api_url' => env('BOLD_API_URL', 'https://api.bold.co/v2'),
    'redirect_url' => env('BOLD_REDIRECT_URL', 'http://localhost:4200/orden-confirmada'),
];
