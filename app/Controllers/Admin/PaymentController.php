<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Services\Payment\PaymentService;
use RuntimeException;

final class PaymentController
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function pending(Request $request, array $params): Response
    {
        return Response::apiSuccess($this->payments->listPendingConfirmations(), 'Danh sách giao dịch chờ xác nhận.');
    }

    public function confirm(Request $request, array $params): Response
    {
        try {
            $payment = $this->payments->confirmManually(
                $params['uuid'],
                $this->currentUserId(),
                $request->input('note')
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'PAYMENT_CONFIRM_FAILED', 422);
        }

        return Response::apiSuccess($payment, 'Xác nhận thanh toán thành công. Học viên đã được cấp quyền truy cập khóa học.');
    }

    public function reject(Request $request, array $params): Response
    {
        $reason = (string) $request->input('reason', '');

        if (trim($reason) === '') {
            return Response::apiError('Vui lòng nhập lý do từ chối.', [], 'VALIDATION_ERROR', 422);
        }

        try {
            $payment = $this->payments->reject($params['uuid'], $this->currentUserId(), $reason);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'PAYMENT_REJECT_FAILED', 422);
        }

        return Response::apiSuccess($payment, 'Đã từ chối giao dịch.');
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['auth_user_id'] ?? $_SERVER['AUTH_USER_ID'] ?? 0);
    }
}
