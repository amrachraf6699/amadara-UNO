<?php

return [
    'api_key' => env('GEMINI_API_KEY', ''),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    'models' => [
        'text' => env('GEMINI_TEXT_MODEL', 'gemini-2.0-flash'),
        'image' => env('GEMINI_IMAGE_MODEL', 'gemini-2.0-flash-exp'),
        'vision' => env('GEMINI_VISION_MODEL', 'gemini-2.0-flash'),
    ],
    'connect_timeout' => env('GEMINI_CONNECT_TIMEOUT', 10),
    'timeout' => env('GEMINI_TIMEOUT', 120),
    'retries' => env('GEMINI_RETRIES', 1),
    'retry_delay' => env('GEMINI_RETRY_DELAY', 1000),
    'log_requests' => env('GEMINI_LOG_REQUESTS', true),
];
