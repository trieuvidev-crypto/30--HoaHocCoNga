<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\ChapterRepository;
use App\Repositories\CourseRepository;
use App\Repositories\LessonRepository;
use App\Repositories\OrderRepository;

final class CourseDetailPageController
{
    public function __construct(
        private readonly CourseRepository $courses,
        private readonly ChapterRepository $chapters,
        private readonly LessonRepository $lessons,
        private readonly OrderRepository $orders
    ) {
    }

    public function show(Request $request, array $params): Response
    {
        $course = $this->courses->findBySlug($params['slug']);

        if ($course === null) {
            return Response::apiError('Không tìm thấy khóa học.', [], 'NOT_FOUND', 404)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }

        $chapters = $this->chapters->findByCourse((int) $course['id']);

        foreach ($chapters as &$chapter) {
            $chapter['lessons'] = $this->lessons->findByChapter((int) $chapter['id']);
        }
        unset($chapter);

        $isEnrolled = false;

        if (!empty($_SESSION['auth_user_id'])) {
            $isEnrolled = $this->orders->findActiveEnrollment((int) $course['id'], (int) $_SESSION['auth_user_id']) !== null;
        }

        $html = View::renderWithLayout('layouts.public', 'pages.courses.show', [
            'pageTitle' => $course['title'],
            'pageDescription' => $course['short_description'] ?? '',
            'course' => $course,
            'chapters' => $chapters,
            'isEnrolled' => $isEnrolled,
        ]);

        return Response::view($html);
    }
}
