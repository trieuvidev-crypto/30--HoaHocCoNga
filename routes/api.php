<?php

declare(strict_types=1);

use App\Controllers\Chemistry\ChemistryController;
use App\Controllers\Course\ChapterController;
use App\Controllers\Course\CourseController;
use App\Controllers\Course\LessonController;
use App\Controllers\Order\OrderController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

/** @var \App\Core\Router $router */

$router->group([
    'prefix' => '/api/v1',
    'middleware' => [SecurityHeadersMiddleware::class, new RateLimitMiddleware('api.default')],
], function ($router) {
    $router->group(['prefix' => '/courses', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]], function ($router) {
        $router->post('/', [CourseController::class, 'store'], [new PermissionMiddleware(['courses.create'])]);
        $router->name('api.courses.store');

        $router->put('/{uuid}', [CourseController::class, 'update'], [new PermissionMiddleware(['courses.edit'])]);
        $router->name('api.courses.update');

        $router->post('/{uuid}/publish', [CourseController::class, 'publish'], [new PermissionMiddleware(['courses.publish'])]);
        $router->name('api.courses.publish');

        $router->post('/{uuid}/archive', [CourseController::class, 'archive'], [new PermissionMiddleware(['courses.edit'])]);
        $router->name('api.courses.archive');

        $router->post('/{uuid}/duplicate', [CourseController::class, 'duplicate'], [new PermissionMiddleware(['courses.create'])]);
        $router->name('api.courses.duplicate');

        $router->delete('/{uuid}', [CourseController::class, 'destroy'], [new PermissionMiddleware(['courses.delete'])]);
        $router->name('api.courses.destroy');

        // Chapters, nested under a course.
        $router->get('/{courseUuid}/chapters', [ChapterController::class, 'index']);
        $router->name('api.chapters.index');

        $router->post('/{courseUuid}/chapters', [ChapterController::class, 'store'], [new PermissionMiddleware(['courses.edit'])]);
        $router->name('api.chapters.store');

        $router->put('/{courseUuid}/chapters/{chapterId}', [ChapterController::class, 'update'], [new PermissionMiddleware(['courses.edit'])]);
        $router->name('api.chapters.update');

        $router->patch('/{courseUuid}/chapters/{chapterId}/reorder', [ChapterController::class, 'reorder'], [new PermissionMiddleware(['courses.edit'])]);
        $router->name('api.chapters.reorder');

        $router->delete('/{courseUuid}/chapters/{chapterId}', [ChapterController::class, 'destroy'], [new PermissionMiddleware(['courses.edit'])]);
        $router->name('api.chapters.destroy');

        // Lessons, nested under a course + chapter.
        $router->get('/{courseUuid}/chapters/{chapterId}/lessons', [LessonController::class, 'index']);
        $router->name('api.lessons.index');

        $router->post('/{courseUuid}/chapters/{chapterId}/lessons', [LessonController::class, 'store'], [new PermissionMiddleware(['lessons.create'])]);
        $router->name('api.lessons.store');

        $router->put('/{courseUuid}/lessons/{lessonId}', [LessonController::class, 'update'], [new PermissionMiddleware(['lessons.edit'])]);
        $router->name('api.lessons.update');

        $router->patch('/{courseUuid}/lessons/{lessonId}/reorder', [LessonController::class, 'reorder'], [new PermissionMiddleware(['lessons.edit'])]);
        $router->name('api.lessons.reorder');

        $router->delete('/{courseUuid}/lessons/{lessonId}', [LessonController::class, 'destroy'], [new PermissionMiddleware(['lessons.edit'])]);
        $router->name('api.lessons.destroy');
    });

    // Orders / Checkout — any authenticated student may check out; the
    // Service layer itself validates enrollment/course-state, not a
    // permission slug (buying a course isn't a role-gated action).
    $router->group(['prefix' => '/orders', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]], function ($router) {
        $router->post('/checkout', [OrderController::class, 'checkout'], [new RateLimitMiddleware('api.default')]);
        $router->name('api.orders.checkout');
    });

    // Chemistry Engine — public tools (Periodic Table, Equation Balancer,
    // Calculators, Compound Search) per HOME_PAGE_SPEC.md §Chemistry Tools.
    // No AuthMiddleware: these are marketing/SEO-valuable public utilities,
    // same as VietJack/Hocmai's public calculator pages.
    $router->group(['prefix' => '/chemistry'], function ($router) {
        $router->get('/compounds/search', [ChemistryController::class, 'searchCompounds']);
        $router->name('api.chemistry.compounds.search');

        $router->get('/compounds/{uuid}', [ChemistryController::class, 'showCompound']);
        $router->name('api.chemistry.compounds.show');

        $router->post('/balance', [ChemistryController::class, 'balanceEquation'], [new RateLimitMiddleware('search')]);
        $router->name('api.chemistry.balance');

        $router->get('/calculator/molar-mass', [ChemistryController::class, 'molarMass']);
        $router->name('api.chemistry.calculator.molar_mass');

        $router->get('/calculator/ph', [ChemistryController::class, 'ph']);
        $router->name('api.chemistry.calculator.ph');

        $router->post('/calculator/dilution', [ChemistryController::class, 'dilution']);
        $router->name('api.chemistry.calculator.dilution');
    });
});

