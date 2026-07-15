<?php

declare(strict_types=1);

namespace App\Controllers\Web\Teacher;

use App\Core\NavigationMenus;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Renders the course-creation form. Submission happens client-side via
 * fetch() against the existing App\Controllers\Course\CourseController
 * API (POST /api/v1/courses) — this controller never duplicates that
 * business logic, per CODING_STANDARDS.md.
 */
final class CourseCreatePageController
{
    public function index(Request $request, array $params): Response
    {
        $html = View::renderWithLayout('layouts.dashboard', 'pages.teacher.course-create', [
            'pageTitle' => 'Tạo khóa học mới',
            'navItems' => NavigationMenus::teacherMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
