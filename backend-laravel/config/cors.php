<?php

// Orígenes permitidos (navegador → API). Separados por coma; usar * para permitir cualquiera (útil solo en desarrollo).
$raw = env('CORS_ALLOWED_ORIGINS');
if ($raw === null || trim((string) $raw) === '') {
    $allowedOrigins = ['*'];
} elseif (trim((string) $raw) === '*') {
    $allowedOrigins = ['*'];
} else {
    $allowedOrigins = array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
