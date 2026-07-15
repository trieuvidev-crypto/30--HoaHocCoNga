<?php

declare(strict_types=1);

use App\Controllers\Auth\AuthController;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

/** @var \App\Core\Router $router */

$router->group([
    'prefix' => '/api/v1/auth',
    'middleware' => [SecurityHeadersMiddleware::class, CsrfMiddleware::class],
], function ($router) {
    $router->post('/register', [AuthController::class, 'register'], [
        new RateLimitMiddleware('auth.register'),
    ]);
    $router->name('auth.register');

    $router->post('/login', [AuthController::class, 'login'], [
        new RateLimitMiddleware('auth.login'),
    ]);
    $router->name('auth.login');

    $router->post('/logout', [AuthController::class, 'logout']);
    $router->name('auth.logout');

    $router->post('/refresh', [AuthController::class, 'refresh']);
    $router->name('auth.refresh');

    $router->post('/forgot-password', [AuthController::class, 'forgotPassword'], [
        new RateLimitMiddleware('auth.password_reset'),
    ]);
    $router->name('auth.forgot-password');

    $router->post('/reset-password', [AuthController::class, 'resetPassword'], [
        new RateLimitMiddleware('auth.password_reset'),
    ]);
    $router->name('auth.reset-password');

    $router->post('/verify-email', [AuthController::class, 'verifyEmail']);
    $router->name('auth.verify-email');

    $router->post('/resend-verification', [AuthController::class, 'resendVerification'], [
        new RateLimitMiddleware('auth.password_reset'),
    ]);
    $router->name('auth.resend-verification');
});
