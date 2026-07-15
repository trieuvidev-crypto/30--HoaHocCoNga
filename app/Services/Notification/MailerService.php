<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Core\Logger;
use RuntimeException;

/**
 * Sends transactional email. Uses authenticated SMTP (via SmtpClient)
 * when config('mail.smtp.host') is set; otherwise falls back to PHP's
 * built-in mail() function, which requires zero configuration on
 * standard cPanel hosting. Failures are logged, never thrown back into
 * request-handling code paths that must not fail just because email
 * delivery had a transient issue — callers that truly need to know
 * about failure can check the boolean return value.
 */
final class MailerService
{
    public function __construct(private readonly Logger $logger)
    {
    }

    public function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        $fromEmail = config('mail.from_address');
        $fromName = config('mail.from_name');

        try {
            if (config('mail.smtp.host')) {
                $client = new SmtpClient(
                    config('mail.smtp.host'),
                    (int) config('mail.smtp.port', 587),
                    config('mail.smtp.username', ''),
                    config('mail.smtp.password', ''),
                    config('mail.smtp.encryption', 'tls')
                );

                $client->send($fromEmail, $fromName, $toEmail, $subject, $htmlBody);
            } else {
                $this->sendViaPhpMail($fromEmail, $fromName, $toEmail, $subject, $htmlBody);
            }

            $this->logger->info('email', 'Gửi email thành công.', ['to' => $toEmail, 'subject' => $subject]);

            return true;
        } catch (RuntimeException $e) {
            $this->logger->error('email', 'Gửi email thất bại: ' . $e->getMessage(), ['to' => $toEmail]);

            return false;
        }
    }

    private function sendViaPhpMail(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody): void
    {
        $headers = implode("\r\n", [
            'From: =?UTF-8?B?' . base64_encode($fromName) . "?= <{$fromEmail}>",
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ]);

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        if (!mail($toEmail, $encodedSubject, $htmlBody, $headers)) {
            throw new RuntimeException('Hàm mail() của PHP trả về thất bại.');
        }
    }
}
