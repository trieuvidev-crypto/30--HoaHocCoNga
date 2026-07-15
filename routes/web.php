<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\Web\AuthPageController;
use App\Controllers\Web\ChemistryToolsPageController;
use App\Controllers\Web\CourseDetailPageController;
use App\Controllers\Web\CoursePageController;
use App\Controllers\Web\DashboardController;
use App\Controllers\Web\LessonPageController;
use App\Controllers\Web\MyCoursesPageController;
use App\Controllers\Web\OrderPaymentPageController;
use App\Controllers\Web\VerifyEmailPageController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

/** @var \App\Core\Router $router */

$router->group(['middleware' => [SecurityHeadersMiddleware::class, CsrfMiddleware::class]], function ($router) {
    $router->get('/', [HomeController::class, 'index']);
    $router->name('home');

    $router->get('/health', [HomeController::class, 'health']);
    $router->name('health');

    $router->get('/login', [AuthPageController::class, 'login']);
    $router->name('login');

    $router->get('/register', [AuthPageController::class, 'register']);
    $router->name('register');

    $router->get('/verify-email', [VerifyEmailPageController::class, 'show']);
    $router->name('verify-email');

    $router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
    $router->name('dashboard');

    $router->get('/dashboard/courses', [MyCoursesPageController::class, 'index'], [AuthMiddleware::class]);
    $router->name('dashboard.my-courses');

    $router->get('/courses', [CoursePageController::class, 'index']);
    $router->name('courses.index');

    $router->get('/courses/{slug}', [CourseDetailPageController::class, 'show']);
    $router->name('courses.show');

    $router->get('/chemistry-tools', [ChemistryToolsPageController::class, 'index']);
    $router->name('chemistry-tools');

    $router->get('/orders/{uuid}/payment', [OrderPaymentPageController::class, 'show'], [AuthMiddleware::class]);
    $router->name('orders.payment');

    $router->get('/dashboard/course/{uuid}', [LessonPageController::class, 'show'], [AuthMiddleware::class]);
    $router->name('dashboard.course');
});
