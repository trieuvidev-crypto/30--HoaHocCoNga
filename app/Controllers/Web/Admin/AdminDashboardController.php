<?php

declare(strict_types=1);

namespace App\Controllers\Web\Admin;

use App\Core\NavigationMenus;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\Payment\PaymentService;

/**
 * Minimal admin overview — per ADMIN_UX.md's requirement that the
 * dashboard answer "what requires attention". Only shows the pending
 * payment count for now (the one queue that actually exists and has a
 * real query behind it); other widgets described in ADMIN_DASHBOARD_SPEC.md
 * (revenue charts, server health, etc.) require aggregation queries not
 * yet built — omitted rather than faked.
 */
final class AdminDashboardController
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function index(Request $request, array $params): Response
    {
        $pendingPayments = $this->payments->listPendingConfirmations();

        $html = View::renderWithLayout('layouts.dashboard', 'pages.admin.dashboard', [
            'pageTitle' => 'Bảng điều khiển quản trị',
            'pendingPaymentsCount' => count($pendingPayments),
            'navItems' => NavigationMenus::adminMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
