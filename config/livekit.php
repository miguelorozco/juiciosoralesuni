<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LiveKit Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for LiveKit SFU server integration.
    |
    */

    'api_key' => env('LIVEKIT_API_KEY'),
    'api_secret' => env('LIVEKIT_API_SECRET'),
    'host' => env('LIVEKIT_HOST', 'ws://localhost:7880'),
    'http_url' => env('LIVEKIT_HTTP_URL', 'http://localhost:7880'),

    /*
    |--------------------------------------------------------------------------
    | coturn (TURN/STUN) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for coturn server for NAT traversal.
    |
    */

    'coturn' => [
        'host' => env('COTURN_HOST', 'localhost'),
        'port' => env('COTURN_PORT', 3478),
        'username' => env('COTURN_USERNAME'),
        'password' => env('COTURN_PASSWORD'),
        'realm' => env('COTURN_REALM', 'juiciosoralesuni'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Room Configuration
    |--------------------------------------------------------------------------
    */

    'room' => [
        'default_name' => 'juicio-room',
        'max_participants' => 50,
        'empty_timeout' => 600, // seconds
        'auto_create' => true,
    ],
];
