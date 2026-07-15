<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Middleware\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Applies the configured security headers to every response.
 * The actual header write happens in Response::send(); this middleware
 * exists so header policy is visible in the pipeline and can be
 * bypassed per-route (e.g. for a webhook endpoint) if ever needed.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
