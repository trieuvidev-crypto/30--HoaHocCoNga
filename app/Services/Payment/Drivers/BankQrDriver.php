<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Core\Database;
use App\Services\Payment\PaymentDriverInterface;
use App\Services\Payment\VietQrPayloadBuilder;
use RuntimeException;

/**
 * The only fully working payment driver in this phase (see
 * config/payment.php — vnpay/momo/zalopay are intentionally unimplemented
 * adapter stubs, not wired to `default`). Generates a VietQR bank-transfer
 * payload; the actual "payment succeeded" transition happens via manual
 * admin confirmation (requiresManualConfirmation() = true), matching how
 * Vietnamese bank-transfer-based e-commerce commonly operates without a
 * paid gateway subscription.
 */
final class BankQrDriver implements PaymentDriverInterface
{
    public function __construct(
        private readonly Database $db,
        private readonly VietQrPayloadBuilder $qrBuilder
    ) {
    }

    /**
     * @return array{qr_payload: string, bank_name: string, account_number: string, account_holder: string, expires_at: string}
     * @throws RuntimeException if no active receiving bank account is configured
     */
    public function initiate(string $transactionNumber, float $amount, string $note): array
    {
        $account = $this->db->fetchOne(
            'SELECT * FROM payment_bank_accounts WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
        );

        if ($account === null) {
            throw new RuntimeException('Hệ thống chưa cấu hình tài khoản ngân hàng nhận thanh toán. Vui lòng liên hệ quản trị viên.');
        }

        $payload = $this->qrBuilder->build(
            $account['bank_bin'],
            $account['account_number'],
            $amount,
            $note !== '' ? $note : $transactionNumber
        );

        $expiryMinutes = (int) config('payment.drivers.bank_qr.confirmation_expiry_minutes', 30);

        return [
            'qr_payload' => $payload,
            'bank_name' => $account['bank_name'],
            'account_number' => $account['account_number'],
            'account_holder' => $account['account_holder'],
            'expires_at' => date('Y-m-d H:i:s', time() + $expiryMinutes * 60),
        ];
    }

    public function requiresManualConfirmation(): bool
    {
        return true;
    }
}
