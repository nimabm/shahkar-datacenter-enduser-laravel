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
    | Reseller Code
    |--------------------------------------------------------------------------
    | Sent as "resellerCode" on every V9.2 request. Not used by the current
    | (OTP) flow. Leave empty if you only use the current flow.
    */
    'reseller_code' => env('SHAHKAR_RESELLER_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    | Which document version handles calls made without ShahkarDataCenter::
    | version(...). Each version maps to its own API document, keyed by the
    | version number printed on that document:
    |
    |   '9.2'    => Shahkar DC EndUser V9.2 (single-step, no OTP)
    |   '1.0'    => new web service v1.0 (two-step OTP flow)
    |
    | See Shahkar\DataCenter\Enums\ApiVersion for the registered versions.
    */
    'default_version' => env('SHAHKAR_API_VERSION', '9.2'),

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
