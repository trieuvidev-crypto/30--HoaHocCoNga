<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;

final class OrderPaymentPageController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly PaymentRepository $payments
    ) {
    }

    public function show(Request $request, array $params): Response
    {
        $order = $this->orders->findByUuid($params['uuid']);

        if ($order === null || (int) $order['user_id'] !== (int) ($_SESSION['auth_user_id'] ?? 0)) {
            return Response::redirect('/dashboard');
        }

        $payment = $this->payments->findPendingByOrder((int) $order['id']);
        $bankAccount = ($payment !== null && $payment['bank_account_id'] !== null)
            ? $this->payments->findBankAccountById((int) $payment['bank_account_id'])
            : null;

        $html = View::renderWithLayout('layouts.public', 'pages.orders.payment', [
            'pageTitle' => 'Thanh toán đơn hàng',
            'order' => $order,
            'payment' => $payment,
            'bankAccount' => $bankAccount,
        ]);

        return Response::view($html);
    }
}
