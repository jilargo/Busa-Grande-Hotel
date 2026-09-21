<?php

declare(strict_types=1);

return [
    'app' => [
        'name'   => env('APP_NAME', 'Busa Grande Hotel'),
        'env'    => env('APP_ENV', 'production'),
        'debug'  => env('APP_DEBUG', 'false') === 'true',
        'url'    => rtrim(env('APP_URL', 'http://127.0.0.1:8090'), '/'),
        'currency' => env('CURRENCY', '₱'),
    ],
    'db' => [
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', '3306'),
        'name'     => env('DB_DATABASE', 'busa_grande_hotel'),
        'user'     => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
    'session' => [
        'name'     => env('SESSION_NAME', 'busa_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
    ],
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri'  => env('GOOGLE_REDIRECT_URI', ''),
    ],
];