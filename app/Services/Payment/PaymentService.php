<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Core\Database;
use App\Core\Events\EventDispatcher;
use App\Events\PaymentCompletedEvent;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Services\Payment\Drivers\BankQrDriver;
use RuntimeException;

final class PaymentService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly PaymentRepository $payments,
        private readonly BankQrDriver $bankQrDriver,
        private readonly Database $db,
        private readonly EventDispatcher $events
    ) {
    }

    /**
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function initiate(string $orderUuid): array
    {
        $order = $this->orders->findByUuid($orderUuid);

        if ($order === null) {
            throw new RuntimeException('Không tìm thấy đơn hàng.');
        }

        if ($order['status'] === 'paid' || $order['status'] === 'completed') {
            throw new RuntimeException('Đơn hàng này đã được thanh toán.');
        }

        if ($order['status'] !== 'pending' && $order['status'] !== 'waiting_payment') {
            throw new RuntimeException('Đơn hàng không ở trạng thái có thể thanh toán.');
        }

        $existing = $this->payments->findPendingByOrder((int) $order['id']);

        if ($existing !== null && $existing['expires_at'] > date('Y-m-d H:i:s')) {
            return $existing; // reuse the still-valid pending payment instead of generating a new QR
        }

        $bankAccount = $this->payments->getActiveBankAccount();
        $transactionNumber = $order['order_number'] . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $result = $this->bankQrDriver->initiate($transactionNumber, (float) $order['total_amount'], $order['order_number']);

        $payment = $this->payments->create(
            (int) $order['id'],
            $transactionNumber,
            (float) $order['total_amount'],
            $result['qr_payload'],
            $bankAccount['id'] ?? null,
            $result['expires_at']
        );

        $this->orders->updateStatus((int) $order['id'], 'waiting_payment');
        $this->payments->log((int) $payment['id'], 'created', (int) $order['user_id']);

        return $payment;
    }

    /**
     * Admin/staff manual confirmation that funds were received — the
     * only "completion" path for the Bank QR driver, which has no
     * automated callback (see BankQrDriver::requiresManualConfirmation()).
     *
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function confirmManually(string $paymentUuid, int $confirmedByUserId, ?string $note = null): array
    {
        $payment = $this->payments->findByUuid($paymentUuid);

        if ($payment === null) {
            throw new RuntimeException('Không tìm thấy giao dịch thanh toán.');
        }

        if ($payment['status'] !== 'pending') {
            throw new RuntimeException('Giao dịch này không ở trạng thái chờ xác nhận.');
        }

        $order = $this->orders->findById((int) $payment['order_id']);

        if ($order === null) {
            throw new RuntimeException('Không tìm thấy đơn hàng liên quan.');
        }

        $updatedPayment = $this->db->transaction(function () use ($payment, $order, $confirmedByUserId, $note) {
            $updated = $this->payments->markPaid((int) $payment['id'], $confirmedByUserId, $note);
            $this->orders->updateStatus((int) $order['id'], 'paid');
            $this->payments->log((int) $payment['id'], 'manually_verified', $confirmedByUserId, ['note' => $note]);

            return $updated;
        });

        // Enrollment granting happens in a listener, not here — see
        // GrantCourseAccessListener, registered in bootstrap/events.php.
        $this->events->dispatch(new PaymentCompletedEvent((int) $order['id'], $order['uuid'], (int) $order['user_id']));

        return $updatedPayment;
    }

    public function reject(string $paymentUuid, int $rejectedByUserId, string $reason): array
    {
        $payment = $this->payments->findByUuid($paymentUuid);

        if ($payment === null) {
            throw new RuntimeException('Không tìm thấy giao dịch thanh toán.');
        }

        $updated = $this->payments->markFailed((int) $payment['id'], $reason);
        $this->payments->log((int) $payment['id'], 'rejected', $rejectedByUserId, ['reason' => $reason]);

        return $updated;
    }

    /** @return array<int, array<string, mixed>> */
    public function listPendingConfirmations(): array
    {
        return $this->payments->findPendingConfirmations();
    }
}
