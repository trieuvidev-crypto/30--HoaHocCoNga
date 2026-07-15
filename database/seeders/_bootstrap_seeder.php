<?php

declare(strict_types=1);

/**
 * Shared bootstrap included by every seeder script. Loads .env and
 * exposes a raw $pdo connection (seeders run outside the HTTP request
 * lifecycle, so they don't go through App\Core\Database's request-scoped
 * singleton — but they use the exact same connection settings).
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

(function (): void {
    $path = dirname(__DIR__, 2) . '/.env';

    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        putenv(trim($key) . '=' . $_ENV[trim($key)]);
    }
})();

$config = config('database.connections.' . config('database.default'));

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
