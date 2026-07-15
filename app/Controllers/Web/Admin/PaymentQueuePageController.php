<?php

declare(strict_types=1);

namespace App\Controllers\Web\Admin;

use App\Core\NavigationMenus;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\Payment\PaymentService;

/**
 * The page that unblocks the entire commerce flow built in Phase 8:
 * without this, App\Controllers\Admin\PaymentController's API had no
 * UI calling it, meaning no admin could actually confirm a Bank QR
 * transfer through the browser.
 */
final class PaymentQueuePageController
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function index(Request $request, array $params): Response
    {
        $html = View::renderWithLayout('layouts.dashboard', 'pages.admin.payments-queue', [
            'pageTitle' => 'Xác nhận thanh toán',
            'pendingPayments' => $this->payments->listPendingConfirmations(),
            'navItems' => NavigationMenus::adminMenu($request->path()),
        ]);

        return Response::view($html);
    }
}
