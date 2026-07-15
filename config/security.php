<?php

declare(strict_types=1);

return [
    'csrf' => [
        'token_length' => 40,
        'header_name' => 'X-CSRF-Token',
        'field_name' => '_csrf',
        'ttl_minutes' => 120,
    ],

    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'algo' => env('JWT_ALGO', 'HS256'),
        'ttl_minutes' => (int) env('JWT_TTL_MINUTES', '60'),
        'refresh_ttl_days' => (int) env('JWT_REFRESH_TTL_DAYS', '14'),
        'issuer' => env('APP_URL', 'https://hoahoconga.com'),
    ],

    'password' => [
        'algo' => PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2,
        ],
        'min_length' => 10,
        'require_mixed_case' => true,
        'require_number' => true,
        'require_symbol' => true,
        'history_limit' => 5,
        'reuse_block' => true,
    ],

    'rate_limits' => [
        // route_class => [max_attempts, decay_seconds]
        'auth.login' => [5, 300],
        'auth.register' => [5, 3600],
        'auth.password_reset' => [5, 3600],
        'api.default' => [120, 60],
        'search' => [60, 60],
        'upload' => [20, 60],
        'comment' => [10, 60],
    ],

    'headers' => [
        'Content-Security-Policy' => "default-src 'self'; img-src 'self' data: https:; script-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'none';",
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    ],

    'session' => [
        'name' => 'hhcn_session',
        'lifetime_minutes' => (int) env('SESSION_LIFETIME', '120'),
        'secure' => filter_var(env('SESSION_SECURE_COOKIE', 'true'), FILTER_VALIDATE_BOOLEAN),
        'http_only' => true,
        'same_site' => 'Lax',
    ],
];
