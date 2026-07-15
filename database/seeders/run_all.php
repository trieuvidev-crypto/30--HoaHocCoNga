<?php

declare(strict_types=1);

/**
 * Usage: php database/seeders/run_all.php
 * Runs every numbered seeder script in order. Run `php app/Console/migrate.php` first.
 */

$seeders = [
    '0001_roles_permissions_seeder.php',
    '0002_system_reference_seeder.php',
    '0003_chemistry_seeder.php',
    '0004_payment_bank_account_seeder.php',
];

foreach ($seeders as $seeder) {
    echo "\n=== Đang chạy: {$seeder} ===\n";
    require __DIR__ . '/' . $seeder;
}

echo "\n=== Hoàn tất tất cả seeder. ===\n";
