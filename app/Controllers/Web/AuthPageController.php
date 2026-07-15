<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\Rbac\PermissionService;

/**
 * Renders the server-side auth pages. Form submission itself happens
 * via fetch() against the JSON API (App\Controllers\Auth\AuthController)
 * — this controller's only job is producing the HTML shell, per MVC's
 * "Views contain presentation only" / "Controllers stay thin".
 */
final class AuthPageController
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function login(Request $request, array $params): Response
    {
        if (!empty($_SESSION['auth_user_id'])) {
            return Response::redirect($this->postLoginDestination((int) $_SESSION['auth_user_id']));
        }

        return Response::view(View::renderWithLayout('layouts.auth', 'pages.auth.login', [
            'pageTitle' => 'Đăng nhập',
        ]));
    }

    public function register(Request $request, array $params): Response
    {
        if (!empty($_SESSION['auth_user_id'])) {
            return Response::redirect($this->postLoginDestination((int) $_SESSION['auth_user_id']));
        }

        return Response::view(View::renderWithLayout('layouts.auth', 'pages.auth.register', [
            'pageTitle' => 'Đăng ký',
        ]));
    }

    private function postLoginDestination(int $userId): string
    {
        return $this->permissions->hasRole($userId, 'teacher') ? '/teacher/dashboard' : '/dashboard';
    }
}
