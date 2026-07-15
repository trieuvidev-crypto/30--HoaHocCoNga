<?php

declare(strict_types=1);

namespace App\Services\Notification;

use RuntimeException;

/**
 * Minimal SMTP client (RFC 5321 happy path: EHLO, STARTTLS, AUTH LOGIN,
 * MAIL FROM/RCPT TO/DATA). No external library — this is the one piece
 * of email sending logic that genuinely cannot be replaced by PHP's
 * mail() when the platform needs an authenticated SMTP relay (most
 * transactional-email providers require this). MailerService uses this
 * only when config('mail.smtp.host') is set; otherwise it uses mail().
 */
final class SmtpClient
{
    private $socket;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption
    ) {
    }

    /**
     * @throws RuntimeException on any SMTP protocol failure
     */
    public function send(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody): void
    {
        $this->connect();

        try {
            $this->expect($this->read(), 220);
            $this->command("EHLO {$this->hostnameForEhlo()}", 250);

            if ($this->encryption === 'tls') {
                $this->command('STARTTLS', 220);

                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Không thể thiết lập kết nối TLS tới máy chủ email.');
                }

                $this->command("EHLO {$this->hostnameForEhlo()}", 250);
            }

            if ($this->username !== '') {
                $this->command('AUTH LOGIN', 334);
                $this->command(base64_encode($this->username), 334);
                $this->command(base64_encode($this->password), 235);
            }

            $this->command("MAIL FROM:<{$fromEmail}>", 250);
            $this->command("RCPT TO:<{$toEmail}>", 250);
            $this->command('DATA', 354);

            $headers = implode("\r\n", [
                'From: ' . $this->encodeHeader($fromName) . " <{$fromEmail}>",
                "To: <{$toEmail}>",
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ]);

            $this->write($headers . "\r\n\r\n" . $htmlBody . "\r\n.\r\n");
            $this->expect($this->read(), 250);

            $this->command('QUIT', 221);
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
        }
    }

    private function connect(): void
    {
        $prefix = $this->encryption === 'ssl' ? 'ssl://' : '';
        $this->socket = @stream_socket_client(
            "{$prefix}{$this->host}:{$this->port}",
            $errno,
            $errstr,
            15
        );

        if ($this->socket === false) {
            throw new RuntimeException("Không thể kết nối máy chủ SMTP: {$errstr}");
        }
    }

    private function command(string $command, int $expectedCode): string
    {
        $this->write($command . "\r\n");
        $response = $this->read();
        $this->expect($response, $expectedCode);

        return $response;
    }

    private function write(string $data): void
    {
        fwrite($this->socket, $data);
    }

    private function read(): string
    {
        $response = '';

        while (($line = fgets($this->socket, 515)) !== false) {
            $response .= $line;

            // Multi-line SMTP responses use "-" after the code on all but
            // the last line (e.g. "250-STARTTLS" ... "250 OK").
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        return $response;
    }

    private function expect(string $response, int $expectedCode): void
    {
        $actualCode = (int) substr($response, 0, 3);

        if ($actualCode !== $expectedCode) {
            throw new RuntimeException("Lỗi giao thức SMTP (mong đợi {$expectedCode}, nhận {$actualCode}): " . trim($response));
        }
    }

    private function hostnameForEhlo(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
