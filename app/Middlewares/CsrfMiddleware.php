<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Middleware\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Verifies the CSRF token on every state-changing request (form posts
 * and AJAX alike). Tokens are per-session, rotated on login, and expire
 * per config('security.csrf.ttl_minutes').
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const SESSION_KEY = '_csrf_token';
    private const SESSION_ISSUED_AT = '_csrf_issued_at';

    public function handle(Request $request, Closure $next): Response
    {
        $safeMethods = ['GET', 'HEAD', 'OPTIONS'];

        if (in_array($request->method(), $safeMethods, true)) {
            $this->ensureToken();

            return $next($request);
        }

        // CSRF defends against a malicious page silently riding the
        // victim's browser-managed session cookie. A request carrying
        // its own Authorization: Bearer token is not ambient/cookie-based
        // — an attacker's page cannot read or attach the caller's token —
        // so it is not CSRF-able and must not be blocked here. Whether
        // the token itself is valid is AuthMiddleware's job, not this
        // middleware's.
        if ($request->bearerToken() !== null) {
            return $next($request);
        }

        $submitted = $request->header(config('security.csrf.header_name'))
            ?? $request->input(config('security.csrf.field_name'));

        if (!$this->isValid($submitted)) {
            return Response::apiError(
                'Phiên làm việc không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang và thử lại.',
                [],
                'CSRF_TOKEN_MISMATCH',
                419
            );
        }

        return $next($request);
    }

    private function ensureToken(): void
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes((int) config('security.csrf.token_length', 40)));
            $_SESSION[self::SESSION_ISSUED_AT] = time();
        }
    }

    private function isValid(mixed $submitted): bool
    {
        if (!is_string($submitted) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        $ttl = (int) config('security.csrf.ttl_minutes', 120) * 60;
        $issuedAt = (int) ($_SESSION[self::SESSION_ISSUED_AT] ?? 0);

        if (time() - $issuedAt > $ttl) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $submitted);
    }
}
