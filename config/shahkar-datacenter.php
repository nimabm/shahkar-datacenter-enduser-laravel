<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shahkar API Base URL
    |--------------------------------------------------------------------------
    | The base URL for the Shahkar API endpoints.
    */
    'base_url' => env('SHAHKAR_BASE_URL', 'https://api.shahkar.ir'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    | Authentication credentials for the Shahkar API.
    */
    'username' => env('SHAHKAR_USERNAME', ''),
    'password' => env('SHAHKAR_PASSWORD', ''),
    'operator_id' => env('SHAHKAR_OPERATOR_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */
    'timeout' => env('SHAHKAR_TIMEOUT', 30),
    'verify_ssl' => env('SHAHKAR_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'times' => env('SHAHKAR_RETRY_TIMES', 3),
        'sleep' => env('SHAHKAR_RETRY_SLEEP', 1000), // milliseconds
    ],
];
