<?php

declare(strict_types=1);

// Cross-Origin Resource Sharing is deliberately locked to known origins.
// The API is primarily consumed server-to-server with bearer tokens (which is
// not subject to CORS), so browser origins are limited to this application and
// any explicitly configured front-ends. No wildcard is used.

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', (string) env('APP_URL', '')))
)));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => true,
];
