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
    | The operator's own reseller code, sent as the top-level "resellerCode" on
    | Data Center v9.2 and Reseller Code service requests. Not used by the v1.0
    | (OTP) flow. Optional — leave empty if you don't use those services.
    */
    'reseller_code' => env('SHAHKAR_RESELLER_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    | The version returned by ShahkarDataCenter::default(). Prefer the typed
    | accessors v92()/v1() in code; this is only the fallback for callers that
    | resolve the version dynamically. Each version maps to its own API document,
    | keyed by the version number printed on that document:
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
