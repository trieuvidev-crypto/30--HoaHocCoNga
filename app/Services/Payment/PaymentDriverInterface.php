<?php

declare(strict_types=1);

namespace App\Services\Payment;

/**
 * Contract every payment driver must implement. See PAYMENT_ARCHITECTURE.md
 * §Architecture — business logic (PaymentService, built in Phase 8) talks
 * to this interface only, never to a concrete gateway class, so adding
 * VNPay/MoMo/ZaloPay later never requires touching existing order logic.
 */
interface PaymentDriverInterface
{
    /**
     * Prepare whatever the customer needs to complete payment (a QR
     * payload, a redirect URL, etc.) and return it as a driver-specific
     * array the calling Controller/view knows how to render.
     */
    public function initiate(string $transactionNumber, float $amount, string $note): array;

    /**
     * True if this driver requires a human (admin/staff) to manually
     * confirm receipt of funds rather than an automated callback.
     */
    public function requiresManualConfirmation(): bool;
}
