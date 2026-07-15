<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Middleware\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\Rbac\PermissionService;
use Closure;

/**
 * Usage: new PermissionMiddleware('course.publish') on a route, placed
 * after AuthMiddleware in the pipeline. Never trust a role name alone
 * on a route — always check the specific permission slug so access can
 * be reconfigured per ROLE_PERMISSION_MATRIX.md without code changes.
 */
final class PermissionMiddleware implements MiddlewareInterface
{
    /** @param array<int, string> $permissionSlugs any one of these grants access */
    public function __construct(private readonly array $permissionSlugs)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $this->resolveUserId();

        if ($userId === null) {
            return Response::apiError('Bạn cần đăng nhập để thực hiện thao tác này.', [], 'UNAUTHENTICATED', 401);
        }

        /** @var PermissionService $permissions */
        $permissions = \App\Core\Container::getInstance()->make(PermissionService::class);

        if (!$permissions->hasAnyPermission($userId, $this->permissionSlugs)) {
            return Response::apiError(
                'Bạn không có quyền thực hiện thao tác này.',
                [],
                'FORBIDDEN',
                403
            );
        }

        return $next($request);
    }

    private function resolveUserId(): ?int
    {
        if (!empty($_SESSION['auth_user_id'])) {
            return (int) $_SESSION['auth_user_id'];
        }

        if (!empty($_SERVER['AUTH_USER_ID'])) {
            return (int) $_SERVER['AUTH_USER_ID'];
        }

        return null;
    }
}
