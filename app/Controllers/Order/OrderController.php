<?php

declare(strict_types=1);

namespace App\Controllers\Order;

use App\Core\Request;
use App\Core\Response;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;
use RuntimeException;

final class OrderController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymentService $payments
    ) {
    }

    public function checkout(Request $request, array $params): Response
    {
        $courseUuid = (string) $request->input('course_uuid', '');
        $couponCode = $request->input('coupon_code');

        if ($courseUuid === '') {
            return Response::apiError('Vui lòng chọn khóa học cần mua.', [], 'VALIDATION_ERROR', 422);
        }

        try {
            $order = $this->orders->createFromCourse($this->currentUserId(), $courseUuid, $couponCode);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'ORDER_CREATE_FAILED', 422);
        }

        // Free/fully-discounted orders are already 'paid' by OrderRepository —
        // no payment step needed. Otherwise, immediately initiate Bank QR
        // payment so the client gets a QR code in the same round trip.
        if ($order['status'] === 'paid') {
            return Response::apiSuccess($order, 'Đăng ký khóa học miễn phí thành công.', [], 201);
        }

        try {
            $payment = $this->payments->initiate($order['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'PAYMENT_INITIATE_FAILED', 422);
        }

        return Response::apiSuccess(['order' => $order, 'payment' => $payment], 'Tạo đơn hàng thành công.', [], 201);
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['auth_user_id'] ?? $_SERVER['AUTH_USER_ID'] ?? 0);
    }
}
