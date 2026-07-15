<?php

declare(strict_types=1);

if (!function_exists('env')) {
    /**
     * Read a value from the loaded environment, with a default fallback.
     * Never call getenv() directly elsewhere in the codebase.
     */
    function env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Dot-notation access into the merged config array
     * (e.g. config('database.connections.mysql.host')).
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!isset($cache[$file])) {
            $path = dirname(__DIR__) . "/config/{$file}.php";
            $cache[$file] = is_file($path) ? require $path : [];
        }

        $value = $cache[$file];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return dirname(__DIR__) . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('generate_uuid_v4')) {
    /**
     * RFC 4122 v4 UUID generator used for every table's public `uuid` column.
     */
    function generate_uuid_v4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
