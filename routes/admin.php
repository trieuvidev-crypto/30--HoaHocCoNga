<?php

declare(strict_types=1);

use App\Controllers\Admin\PaymentController;
use App\Controllers\Web\Admin\AdminDashboardController;
use App\Controllers\Web\Admin\PaymentQueuePageController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

/** @var \App\Core\Router $router */

/**
 * /administrator routes are registered here as each admin console
 * module ships (User Manager, Course Manager, Payment Center, ...).
 * See ADMIN_UX.md and PROJECT.md §Admin Area for the full sitemap this
 * will grow to cover.
 */
$router->group([
    'prefix' => '/administrator',
    'middleware' => [SecurityHeadersMiddleware::class, AuthMiddleware::class, CsrfMiddleware::class],
], function ($router) {
    $router->get('/dashboard', [AdminDashboardController::class, 'index'], [new PermissionMiddleware(['admin.view'])]);
    $router->name('admin.dashboard');

    $router->group(['prefix' => '/payments'], function ($router) {
        $router->get('/', [PaymentQueuePageController::class, 'index'], [new PermissionMiddleware(['payments.view'])]);
        $router->name('admin.payments.page');

        $router->get('/pending', [PaymentController::class, 'pending'], [new PermissionMiddleware(['payments.view'])]);
        $router->name('admin.payments.pending');

        $router->post('/{uuid}/confirm', [PaymentController::class, 'confirm'], [new PermissionMiddleware(['payments.manage'])]);
        $router->name('admin.payments.confirm');

        $router->post('/{uuid}/reject', [PaymentController::class, 'reject'], [new PermissionMiddleware(['payments.manage'])]);
        $router->name('admin.payments.reject');
    });
});
