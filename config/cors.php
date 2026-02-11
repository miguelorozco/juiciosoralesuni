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

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',       // Unity WebGL servido en puerto 80
        'https://localhost',      // Unity WebGL HTTPS puerto 443
        'http://localhost:3000',  // Unity WebGL
        'https://localhost:3000', // Unity WebGL HTTPS
        'http://127.0.0.1:3000',  // Unity WebGL localhost
        'https://127.0.0.1:3000', // Unity WebGL localhost HTTPS
        'http://localhost:8080',  // Unity Editor
        'https://localhost:8080', // Unity Editor HTTPS
        'http://127.0.0.1:8080',  // Unity Editor localhost
        'https://127.0.0.1:8080', // Unity Editor localhost HTTPS
        'http://localhost:8000',  // Laravel local
        'https://localhost:8000', // Laravel local HTTPS
        'http://127.0.0.1',       // localhost sin puerto
        'https://127.0.0.1',
    ],

    'allowed_origins_patterns' => [
        '/^https?:\/\/localhost:\d+$/',  // Cualquier puerto localhost
        '/^https?:\/\/127\.0\.0\.1:\d+$/', // Cualquier puerto 127.0.0.1
        '/^https?:\/\/.*\.unity3d\.com$/', // Unity Cloud Build
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Unity-Version',
        'X-Unity-Platform',
    ],

    'exposed_headers' => [
        'X-Unity-Status',
        'X-Session-Id',
    ],

    'max_age' => 3600,

    'supports_credentials' => true,

];
