<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NSCRA API Base URL
    |--------------------------------------------------------------------------
    | Base URL for the NSCRA data-center endpoints (put/update/delete/status).
    */
    'base_url' => env('SHAHKAR_BASE_URL', 'https://nscra.ir/api/1.0/external'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    | api_key      -> sent as the X-API-KEY header on every request.
    | client_id    -> your registered client id (embedded in the JWS header).
    | provider_code-> prefix used to build unique requestId values.
    */
    'api_key'       => env('SHAHKAR_API_KEY', ''),
    'client_id'     => env('SHAHKAR_CLIENT_ID', ''),
    'provider_code' => env('SHAHKAR_PROVIDER_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Cryptographic Keys (EC P-256 / prime256v1)
    |--------------------------------------------------------------------------
    | Each value may be either the PEM content itself or a path to a PEM file.
    |
    | client_private_key -> signs requests (ES256) and decrypts responses.
    | client_public_key  -> registered with the server via registerKey().
    | server_public_key  -> encrypts requests (ECDH-ES) and verifies responses.
    |                       Leave empty to use the key bundled with the package;
    |                       set SHAHKAR_SERVER_PUBLIC_KEY only to override it.
    */
    'client_private_key' => env('SHAHKAR_CLIENT_PRIVATE_KEY', ''),
    'client_public_key'  => env('SHAHKAR_CLIENT_PUBLIC_KEY', ''),
    'server_public_key'  => env('SHAHKAR_SERVER_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */
    'timeout'    => env('SHAHKAR_TIMEOUT', 30),
    'verify_ssl' => env('SHAHKAR_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed clock skew (seconds) for JWS/JWE `iat` validation on responses.
    |--------------------------------------------------------------------------
    */
    'clock_skew' => env('SHAHKAR_CLOCK_SKEW', 300),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration (connection failures only)
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'times' => env('SHAHKAR_RETRY_TIMES', 1),
        'sleep' => env('SHAHKAR_RETRY_SLEEP', 1000), // milliseconds
    ],
];
