<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\Auth\AuthService;
use RuntimeException;

final class VerifyEmailPageController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function show(Request $request, array $params): Response
    {
        $token = (string) $request->query('token', '');
        $success = false;
        $message = 'Thiếu mã xác minh.';

        if ($token !== '') {
            try {
                $this->auth->verifyEmail($token);
                $success = true;
                $message = 'Xác minh email thành công! Bạn có thể đăng nhập ngay bây giờ.';
            } catch (RuntimeException $e) {
                $message = $e->getMessage();
            }
        }

        $html = View::renderWithLayout('layouts.auth', 'pages.auth.verify-email', [
            'pageTitle' => 'Xác minh email',
            'success' => $success,
            'message' => $message,
        ]);

        return Response::view($html);
    }
}
