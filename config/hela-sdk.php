<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Source app
    |--------------------------------------------------------------------------
    |
    | Name of the HELA application making requests to Auster. When present, the
    | SDK sends it as X-Hela-App so Auster can log the caller.
    |
    */
    'source' => env('HELA_SDK_APP_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Auster API
    |--------------------------------------------------------------------------
    |
    | Auster uses bearer tokens through App\Http\Middleware\Auth\API\
    | ValidateAccessToken. The base URL should point to the Auster application
    | host, without the /api suffix.
    |
    */
    'auster' => [
        'base_url' => env('HELA_AUSTER_URL', env('HELA_SDK_BASE_URL')),
        'token' => env('HELA_AUSTER_TOKEN', env('HELA_SDK_API_KEY')),
        'clients_api' => [
            'token' => env('HELA_AUSTER_CLIENTS_API_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP options
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('HELA_SDK_TIMEOUT', 30),
    'retry' => [
        'times' => (int) env('HELA_SDK_RETRY_TIMES', 0),
        'sleep' => (int) env('HELA_SDK_RETRY_SLEEP', 100),
    ],

    'base_url' => env('HELA_SDK_BASE_URL'),
    'api_key' => env('HELA_SDK_API_KEY'),
];
