<?php

declare(strict_types=1);

use App\Controllers\Web\Teacher\CourseCreatePageController;
use App\Controllers\Web\Teacher\CourseEditPageController;
use App\Controllers\Web\Teacher\CourseListPageController;
use App\Controllers\Web\Teacher\TeacherDashboardController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

/** @var \App\Core\Router $router */

/**
 * Teacher-facing server-rendered pages. Every action a teacher takes
 * here submits to the existing JSON API (App\Controllers\Course\*) via
 * fetch() — these controllers only render HTML, never duplicate
 * business logic already built in Phase 5.
 */
$router->group([
    'prefix' => '/teacher',
    'middleware' => [SecurityHeadersMiddleware::class, CsrfMiddleware::class, AuthMiddleware::class, new PermissionMiddleware(['courses.create'])],
], function ($router) {
    $router->get('/dashboard', [TeacherDashboardController::class, 'index']);
    $router->name('teacher.dashboard');

    $router->get('/courses', [CourseListPageController::class, 'index']);
    $router->name('teacher.courses.index');

    $router->get('/courses/create', [CourseCreatePageController::class, 'index']);
    $router->name('teacher.courses.create');

    $router->get('/courses/{uuid}/edit', [CourseEditPageController::class, 'show']);
    $router->name('teacher.courses.edit');
});
