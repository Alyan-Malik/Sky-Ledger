<?php
// config/duffel.php

return [
    'api' => [
        'token' => env('DUFFEL_API_TOKEN'),
        'url' => env('DUFFEL_API_URL', 'https://api.duffel.com'),
        'version' => env('DUFFEL_API_VERSION', 'v2'), // Changed from 'beta' to 'v2'
    ],
    'http' => [
    'timeout' => env('DUFFEL_HTTP_TIMEOUT', 60), // Increased from 30 to 60
    'retry' => [
        'times' => env('DUFFEL_RETRY_TIMES', 2),
        'sleep' => env('DUFFEL_RETRY_SLEEP', 2000), // Increased sleep time
        'when' => [429, 500, 502, 503, 504],
    ],
    'connect_timeout' => env('DUFFEL_CONNECT_TIMEOUT', 15), // Increased from 10
],
    'service' => [
        'markup_percentage' => env('DUFFEL_SERVICE_MARKUP_PERCENTAGE', 5),
        'markup_fixed' => env('DUFFEL_SERVICE_MARKUP_FIXED', 0),
        'currency' => env('DUFFEL_DEFAULT_CURRENCY', 'USD'),
    ],
    'cache' => [
        'ttl' => env('DUFFEL_CACHE_TTL', 300),
        'prefix' => 'duffel_',
    ],
    'logging' => [
        'channel' => env('DUFFEL_LOG_CHANNEL', 'stack'),
        'log_requests' => env('DUFFEL_LOG_REQUESTS', true),
        'log_responses' => env('DUFFEL_LOG_RESPONSES', false),
    ],
];