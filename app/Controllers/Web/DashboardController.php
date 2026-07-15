<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CourseRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;

/**
 * Student dashboard overview — per STUDENT_DASHBOARD_SPEC.md. Reads from
 * repositories directly (no dedicated "DashboardService") since this
 * controller only aggregates read-only data for display; it performs no
 * business logic or state changes of its own.
 */
final class DashboardController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly CourseRepository $courses,
        private readonly NotificationRepository $notifications
    ) {
    }

    public function index(Request $request, array $params): Response
    {
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
        $user = $this->users->findById($userId);

        if ($user === null) {
            // Session referenced a user that no longer exists (deleted
            // account) — force back to login rather than showing a
            // broken/empty dashboard.
            unset($_SESSION['auth_user_id'], $_SESSION['auth_user_uuid']);

            return Response::redirect('/login');
        }

        $html = View::renderWithLayout('layouts.dashboard', 'pages.dashboard.index', [
            'pageTitle' => 'Tổng quan',
            'user' => $user,
            'enrolledCourses' => $this->courses->findEnrolledCoursesForStudent($userId, 12),
            'unreadNotifications' => $this->notifications->getUnreadForUser($userId, 20),
            'navItems' => \App\Core\NavigationMenus::studentMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
