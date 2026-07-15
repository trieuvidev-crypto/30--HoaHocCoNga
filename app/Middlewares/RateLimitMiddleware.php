<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Database;
use App\Core\Middleware\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Fixed-window rate limiter backed by the `rate_limit_hits` table.
 * No Redis dependency (disallowed on the cPanel hosting target).
 *
 * Usage: bind a specific bucket name per route group, e.g.
 * new RateLimitMiddleware('auth.login').
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $bucket = 'api.default')
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        [$maxAttempts, $decaySeconds] = config("security.rate_limits.{$this->bucket}", [120, 60]);

        $db = Database::getInstance();
        $key = $this->bucket . ':' . $request->ip();
        $windowStart = time() - $decaySeconds;

        $db->query(
            'DELETE FROM rate_limit_hits WHERE bucket_key = :key AND hit_at < :window_start',
            ['key' => $key, 'window_start' => date('Y-m-d H:i:s', $windowStart)]
        );

        $count = (int) ($db->fetchOne(
            'SELECT COUNT(*) AS total FROM rate_limit_hits WHERE bucket_key = :key',
            ['key' => $key]
        )['total'] ?? 0);

        if ($count >= $maxAttempts) {
            return Response::apiError(
                'Bạn đã thao tác quá nhiều lần. Vui lòng thử lại sau ít phút.',
                [],
                'RATE_LIMITED',
                429
            )->withHeader('Retry-After', (string) $decaySeconds);
        }

        $db->query(
            'INSERT INTO rate_limit_hits (bucket_key, hit_at) VALUES (:key, :now)',
            ['key' => $key, 'now' => date('Y-m-d H:i:s')]
        );

        return $next($request);
    }
}
