<?php

declare(strict_types=1);

namespace App\Controllers\Web\Teacher;

use App\Core\NavigationMenus;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CourseRepository;
use App\Repositories\UserRepository;

/**
 * Per TEACHER_DASHBOARD_SPEC.md — overview of the teacher's own courses.
 * Revenue/analytics widgets are intentionally NOT shown yet: this phase
 * has no revenue-aggregation query built, and showing a zero/fake number
 * would violate "never generate placeholder implementations" more than
 * simply omitting the widget until Phase 8's analytics work lands.
 */
final class TeacherDashboardController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly CourseRepository $courses
    ) {
    }

    public function index(Request $request, array $params): Response
    {
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
        $user = $this->users->findById($userId);

        if ($user === null) {
            unset($_SESSION['auth_user_id'], $_SESSION['auth_user_uuid']);

            return Response::redirect('/login');
        }

        $html = View::renderWithLayout('layouts.dashboard', 'pages.teacher.dashboard', [
            'pageTitle' => 'Tổng quan giảng dạy',
            'user' => $user,
            'courses' => $this->courses->findByTeacher($userId, 6),
            'navItems' => NavigationMenus::teacherMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
