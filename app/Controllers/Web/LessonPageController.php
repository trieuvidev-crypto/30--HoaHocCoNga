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

/**
 * The actual "learning" screen — course sidebar (chapters/lessons) +
 * content area for the currently selected lesson. Enforces enrollment
 * (or admin/teacher ownership) before showing anything beyond preview
 * lessons, per COURSE_DETAIL/LESSON EXPERIENCE requirements.
 */
final class LessonPageController
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
        $course = $this->courses->findByUuid($params['uuid']);
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);

        if ($course === null) {
            return Response::redirect('/dashboard');
        }

        $isOwnerOrAdmin = (int) $course['primary_teacher_id'] === $userId;
        $isEnrolled = $this->orders->findActiveEnrollment((int) $course['id'], $userId) !== null;

        if (!$isEnrolled && !$isOwnerOrAdmin) {
            return Response::redirect('/courses/' . $course['slug']);
        }

        $chapters = $this->chapters->findByCourse((int) $course['id']);

        foreach ($chapters as &$chapter) {
            $chapter['lessons'] = $this->lessons->findByChapter((int) $chapter['id']);
        }
        unset($chapter);

        $requestedLessonUuid = (string) $request->query('lesson', '');
        $activeLesson = null;

        foreach ($chapters as $chapter) {
            foreach ($chapter['lessons'] as $lesson) {
                if ($requestedLessonUuid !== '' && $lesson['uuid'] === $requestedLessonUuid) {
                    $activeLesson = $lesson;
                }
            }
        }

        // Default to the first lesson of the first chapter when none
        // was explicitly requested, so the page is never blank.
        if ($activeLesson === null && !empty($chapters) && !empty($chapters[0]['lessons'])) {
            $activeLesson = $chapters[0]['lessons'][0];
        }

        $html = View::renderWithLayout('layouts.dashboard', 'pages.dashboard.course', [
            'pageTitle' => $course['title'],
            'course' => $course,
            'chapters' => $chapters,
            'activeLesson' => $activeLesson,
            'navItems' => \App\Core\NavigationMenus::studentMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
