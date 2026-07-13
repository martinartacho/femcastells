<?php

return [
    'paths' => ['api/*', 'api-fempinya/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Accept', 'Authorization', 'X-Requested-With', 'ngrok-skip-browser-warning'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
