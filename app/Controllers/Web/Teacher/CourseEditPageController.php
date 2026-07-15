<?php

declare(strict_types=1);

namespace App\Controllers\Web\Teacher;

use App\Core\NavigationMenus;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Policies\CoursePolicy;
use App\Repositories\ChapterRepository;
use App\Repositories\CourseRepository;
use App\Repositories\LessonRepository;

final class CourseEditPageController
{
    public function __construct(
        private readonly CourseRepository $courses,
        private readonly ChapterRepository $chapters,
        private readonly LessonRepository $lessons,
        private readonly CoursePolicy $policy
    ) {
    }

    public function show(Request $request, array $params): Response
    {
        $course = $this->courses->findByUuid($params['uuid']);
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);

        if ($course === null) {
            return Response::redirect('/teacher/courses');
        }

        $assistantIds = $this->courses->getAssistantTeacherIds((int) $course['id']);

        if (!$this->policy->canManage($userId, $course, $assistantIds)) {
            return Response::redirect('/teacher/courses');
        }

        $chapters = $this->chapters->findByCourse((int) $course['id']);

        foreach ($chapters as &$chapter) {
            $chapter['lessons'] = $this->lessons->findByChapter((int) $chapter['id']);
        }
        unset($chapter);

        $html = View::renderWithLayout('layouts.dashboard', 'pages.teacher.course-edit', [
            'pageTitle' => 'Chỉnh sửa: ' . $course['title'],
            'course' => $course,
            'chapters' => $chapters,
            'navItems' => NavigationMenus::teacherMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
