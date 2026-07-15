<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'HoaHocCoNga.Com'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(env('APP_URL', 'https://hoahoconga.com'), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'locale' => env('APP_LOCALE', 'vi'),
    'fallback_locale' => 'en',
    'key' => env('APP_KEY', ''),

    /**
     * Providers registered at boot. Each provider is responsible for
     * wiring one concern into the DI container. Order matters: earlier
     * providers must not depend on later ones.
     */
    'providers' => [
        \App\Core\Providers\ConfigServiceProvider::class,
        \App\Core\Providers\DatabaseServiceProvider::class,
        \App\Core\Providers\LoggingServiceProvider::class,
        \App\Core\Providers\SecurityServiceProvider::class,
        \App\Core\Providers\SessionServiceProvider::class,
        \App\Core\Providers\RouteServiceProvider::class,
    ],
];
