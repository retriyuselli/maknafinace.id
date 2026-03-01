<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost,http://127.0.0.1:8000')),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN'],
    'exposed_headers' => ['Content-Length', 'Content-Type', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age' => 3600,
    'supports_credentials' => false,
];
