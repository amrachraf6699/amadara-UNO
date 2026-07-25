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
    // A three-player double round robin requires six detailed match reports.
    // Gemini counts hidden thinking tokens against this budget as well.
    'simulation_max_output_tokens' => env('GEMINI_SIMULATION_MAX_OUTPUT_TOKENS', 32768),
    'log_requests' => env('GEMINI_LOG_REQUESTS', true),
    'log_response' => env('GEMINI_LOG_RESPONSE', true),
];
