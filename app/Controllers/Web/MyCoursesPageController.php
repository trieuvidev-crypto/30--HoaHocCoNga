<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CourseRepository;

/**
 * "My Courses" — the full enrolled-course list, distinct from the
 * dashboard overview (which only teases a few). Per
 * STUDENT_DASHBOARD_SPEC.md's dedicated "Course Progress" list.
 */
final class MyCoursesPageController
{
    public function __construct(private readonly CourseRepository $courses)
    {
    }

    public function index(Request $request, array $params): Response
    {
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);

        $html = View::renderWithLayout('layouts.dashboard', 'pages.dashboard.my-courses', [
            'pageTitle' => 'Khóa học của tôi',
            'enrolledCourses' => $this->courses->findEnrolledCoursesForStudent($userId, 100),
            'navItems' => \App\Core\NavigationMenus::studentMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
