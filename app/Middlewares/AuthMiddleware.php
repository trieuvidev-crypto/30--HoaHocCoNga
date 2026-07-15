<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Middleware\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\Auth\TokenService;
use Closure;

/**
 * Authenticates the request via session (server-rendered pages) or
 * JWT bearer token (API / Socket.IO handshake origin requests).
 * On success, injects `auth_user_id` / `auth_user_uuid` into $_SERVER
 * for downstream controllers to read via Request — no global state.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly TokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!empty($_SESSION['auth_user_id'])) {
            return $next($request);
        }

        $bearer = $request->bearerToken();

        if ($bearer !== null) {
            $payload = $this->tokens->validateAccessToken($bearer);

            if ($payload !== null) {
                $_SERVER['AUTH_USER_ID'] = $payload['sub'];
                $_SERVER['AUTH_USER_UUID'] = $payload['uuid'];

                return $next($request);
            }
        }

        return Response::apiError('Bạn cần đăng nhập để thực hiện thao tác này.', [], 'UNAUTHENTICATED', 401);
    }
}
