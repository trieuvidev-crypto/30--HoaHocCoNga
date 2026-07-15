<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CourseRepository;

final class CoursePageController
{
    public function __construct(private readonly CourseRepository $courses)
    {
    }

    public function index(Request $request, array $params): Response
    {
        $categoryId = $request->query('category') !== null ? (int) $request->query('category') : null;
        $gradeId = $request->query('grade') !== null ? (int) $request->query('grade') : null;

        $html = View::renderWithLayout('layouts.public', 'pages.courses.index', [
            'pageTitle' => 'Khóa học Hóa học',
            'pageDescription' => 'Khám phá các khóa học Hóa học từ lớp 8 đến luyện thi Đại học và Olympic.',
            'courses' => $this->courses->findPublished($categoryId, $gradeId),
        ]);

        return Response::view($html);
    }
}
