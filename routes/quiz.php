<?php

declare(strict_types=1);

use App\Controllers\Quiz\QuestionController;
use App\Controllers\Quiz\QuizAttemptController;
use App\Controllers\Quiz\QuizController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

/** @var \App\Core\Router $router */

$router->group([
    'prefix' => '/api/v1',
    'middleware' => [SecurityHeadersMiddleware::class, CsrfMiddleware::class, new RateLimitMiddleware('api.default')],
], function ($router) {
    // Question Bank — teacher/admin management.
    $router->group(['prefix' => '/questions', 'middleware' => [AuthMiddleware::class]], function ($router) {
        $router->get('/', [QuestionController::class, 'index']);
        $router->name('api.questions.index');

        $router->post('/', [QuestionController::class, 'store'], [new PermissionMiddleware(['courses.create'])]);
        $router->name('api.questions.store');

        $router->get('/{uuid}', [QuestionController::class, 'show']);
        $router->name('api.questions.show');

        $router->post('/{uuid}/publish', [QuestionController::class, 'publish'], [new PermissionMiddleware(['courses.publish'])]);
        $router->name('api.questions.publish');
    });

    // Quiz management — teacher/admin.
    $router->group(['prefix' => '/quizzes', 'middleware' => [AuthMiddleware::class]], function ($router) {
        $router->post('/', [QuizController::class, 'store'], [new PermissionMiddleware(['courses.create'])]);
        $router->name('api.quizzes.store');

        $router->get('/{uuid}/questions', [QuizController::class, 'questions']);
        $router->name('api.quizzes.questions');

        $router->post('/{uuid}/questions', [QuizController::class, 'addQuestion'], [new PermissionMiddleware(['courses.edit'])]);
        $router->name('api.quizzes.questions.add');

        $router->delete('/{uuid}/questions/{questionUuid}', [QuizController::class, 'removeQuestion'], [new PermissionMiddleware(['courses.edit'])]);
        $router->name('api.quizzes.questions.remove');

        $router->post('/{uuid}/publish', [QuizController::class, 'publish'], [new PermissionMiddleware(['courses.publish'])]);
        $router->name('api.quizzes.publish');

        // Student-facing: take the quiz.
        $router->post('/{uuid}/start', [QuizAttemptController::class, 'start']);
        $router->name('api.quizzes.start');

        $router->get('/{uuid}/history', [QuizAttemptController::class, 'history']);
        $router->name('api.quizzes.history');
    });

    $router->group(['prefix' => '/quiz-attempts', 'middleware' => [AuthMiddleware::class]], function ($router) {
        $router->post('/{attemptUuid}/answer', [QuizAttemptController::class, 'answer']);
        $router->name('api.quiz-attempts.answer');

        $router->post('/{attemptUuid}/finish', [QuizAttemptController::class, 'finish']);
        $router->name('api.quiz-attempts.finish');
    });
});
