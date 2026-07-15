<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

/**
 * Load .env into $_ENV without any third-party parser dependency.
 */
(function (): void {
    $path = dirname(__DIR__) . '/.env';

    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
})();

date_default_timezone_set((string) config('app.timezone', 'UTC'));

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', base_path('storage/logs/php-error.log'));

set_exception_handler(function (Throwable $e): void {
    error_log('[UNCAUGHT] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'Đã xảy ra lỗi ngoài dự kiến. Vui lòng thử lại sau.',
        'data' => null,
        'errors' => [],
        'error_code' => 'INTERNAL_ERROR',
        'timestamp' => gmdate('c'),
    ]);
});

// Secure session cookie params before any session_start() call.
$sessionConfig = config('security.session');
session_set_cookie_params([
    'lifetime' => $sessionConfig['lifetime_minutes'] * 60,
    'path' => '/',
    'secure' => $sessionConfig['secure'],
    'httponly' => $sessionConfig['http_only'],
    'samesite' => $sessionConfig['same_site'],
]);
session_name($sessionConfig['name']);
session_start();

$container = Container::getInstance();

// Database is a singleton with a private constructor (see App\Core\Database).
// Bind it explicitly so autowiring can inject it into Repositories/Services.
$container->singleton(\App\Core\Database::class, fn () => \App\Core\Database::getInstance());
$container->singleton(\App\Core\Cache::class, fn () => \App\Core\Cache::getInstance());
$container->singleton(\App\Core\Logger::class, fn () => new \App\Core\Logger());
$container->singleton(
    \App\Core\Events\EventDispatcher::class,
    fn (Container $c) => \App\Core\Events\EventDispatcher::getInstance($c, $c->make(\App\Core\Logger::class))
);

// Event → Listener registrations. This is the single place that wires
// which listeners react to which domain events — see HOOK_EVENT_SYSTEM.md.
// Adding a new reaction to an existing event never requires touching the
// Service that fires it.
require __DIR__ . '/events.php';

$router = new Router();

// Route files are loaded in a scope where $router is available; each
// file calls $router->get()/post()/group() etc. to register its routes.
$routeFiles = ['web.php', 'auth.php', 'api.php', 'admin.php', 'teacher.php', 'quiz.php'];

foreach ($routeFiles as $file) {
    $path = dirname(__DIR__) . "/routes/{$file}";

    if (is_file($path)) {
        require $path;
    }
}

return [$container, $router];
