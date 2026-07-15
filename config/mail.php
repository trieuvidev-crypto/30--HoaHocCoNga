<?php

declare(strict_types=1);

return [
    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@hoahoconga.com'),
    'from_name' => env('MAIL_FROM_NAME', 'HoaHocCoNga.Com'),

    // SMTP is optional — when host/username are empty, MailerService
    // falls back to PHP's built-in mail() function, which works out of
    // the box on cPanel shared hosting without any extra configuration.
    'smtp' => [
        'host' => env('MAIL_HOST', ''),
        'port' => (int) env('MAIL_PORT', '587'),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    ],
];
