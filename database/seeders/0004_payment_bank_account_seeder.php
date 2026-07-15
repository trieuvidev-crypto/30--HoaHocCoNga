<?php

declare(strict_types=1);

/**
 * Usage: php database/seeders/0004_payment_bank_account_seeder.php
 *
 * Seeds one placeholder receiving bank account so BankQrDriver has
 * something to generate a QR against out of the box. The admin MUST
 * replace these values with real account details before accepting real
 * payments — this is demo/development data, not a real bank account.
 */

require_once __DIR__ . '/../../bootstrap/helpers.php';
require_once __DIR__ . '/_bootstrap_seeder.php';

/** @var PDO $pdo */

$exists = $pdo->query('SELECT COUNT(*) FROM payment_bank_accounts')->fetchColumn();

if ((int) $exists > 0) {
    echo "Đã có tài khoản ngân hàng, bỏ qua seed.\n";
} else {
    $pdo->prepare(
        'INSERT INTO payment_bank_accounts (bank_name, bank_bin, account_number, account_holder, is_active)
         VALUES (:bank_name, :bank_bin, :account_number, :account_holder, 1)'
    )->execute([
        'bank_name' => 'Ngân hàng TMCP Ngoại thương Việt Nam (Vietcombank) — DEMO',
        'bank_bin' => '970436', // Vietcombank's official VietQR bank BIN
        'account_number' => '0000000000',
        'account_holder' => 'CONG TY TNHH HOAHOCCONGA',
    ]);

    echo "Đã seed 1 tài khoản ngân hàng DEMO. ⚠️  BẮT BUỘC thay bằng thông tin thật trước khi vận hành production.\n";
}
