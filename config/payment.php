<?php

declare(strict_types=1);

/**
 * Payment driver configuration.
 *
 * Only `bank_qr` is a real, working implementation in this phase.
 * Other keys exist purely as documented extension points (interface
 * contracts the Payment Service depends on) — they are NOT wired to
 * any live gateway and must never be selected as `default`.
 */
return [
    'default' => env('PAYMENT_DEFAULT_DRIVER', 'bank_qr'),

    'drivers' => [
        'bank_qr' => [
            'class' => \App\Services\Payment\Drivers\BankQrDriver::class,
            'enabled' => true,
            'bank_name' => env('PAYMENT_BANK_NAME', ''),
            'account_number' => env('PAYMENT_BANK_ACCOUNT_NUMBER', ''),
            'account_holder' => env('PAYMENT_BANK_ACCOUNT_HOLDER', ''),
            'qr_template' => env('PAYMENT_BANK_QR_TEMPLATE', 'compact'),
            // VietQR-compatible payload format (EMV QR Code for bank transfer)
            'qr_provider' => 'vietqr',
            'manual_confirmation' => true,
            'confirmation_expiry_minutes' => 30,
        ],

        // Adapter contracts only — implementing class intentionally
        // does not exist yet. Enabling any of these before a real
        // integration is built must fail fast (see PaymentServiceProvider).
        'vnpay' => ['class' => null, 'enabled' => false],
        'momo' => ['class' => null, 'enabled' => false],
        'zalopay' => ['class' => null, 'enabled' => false],
    ],

    'order' => [
        'number_prefix' => 'HHCN',
        'expire_pending_after_minutes' => 30,
        'currency' => 'VND',
    ],
];
