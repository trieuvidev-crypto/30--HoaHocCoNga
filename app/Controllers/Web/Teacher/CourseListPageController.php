<?php

declare(strict_types=1);

namespace App\Controllers\Web\Teacher;

use App\Core\NavigationMenus;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CourseRepository;

final class CourseListPageController
{
    public function __construct(private readonly CourseRepository $courses)
    {
    }

    public function index(Request $request, array $params): Response
    {
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);

        $html = View::renderWithLayout('layouts.dashboard', 'pages.teacher.courses-index', [
            'pageTitle' => 'Khóa học của tôi',
            'courses' => $this->courses->findByTeacher($userId, 100),
            'navItems' => NavigationMenus::teacherMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
