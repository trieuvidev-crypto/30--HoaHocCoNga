<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Structured logger writing one JSON line per entry to
 * storage/logs/{category}.log, per LOGGING_STANDARD.md.
 *
 * Never pass raw request payloads into $context without stripping
 * sensitive fields first — this class defensively redacts common
 * sensitive keys as a last line of defense, but callers are still
 * responsible for not logging secrets in the message itself.
 */
final class Logger
{
    private const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    private const REDACTED_KEYS = [
        'password', 'password_hash', 'token', 'access_token', 'refresh_token',
        'secret', 'card_number', 'cvv', 'api_key', 'authorization',
    ];

    public function log(string $level, string $category, string $message, array $context = []): void
    {
        if (!in_array($level, self::LEVELS, true)) {
            $level = 'info';
        }

        $entry = [
            'timestamp' => gmdate('c'),
            'level' => $level,
            'category' => $category,
            'message' => $message,
            'context' => $this->redact($context),
        ];

        $directory = storage_path('logs');

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents("{$directory}/{$category}.log", $line, FILE_APPEND | LOCK_EX);
    }

    public function debug(string $category, string $message, array $context = []): void
    {
        $this->log('debug', $category, $message, $context);
    }

    public function info(string $category, string $message, array $context = []): void
    {
        $this->log('info', $category, $message, $context);
    }

    public function warning(string $category, string $message, array $context = []): void
    {
        $this->log('warning', $category, $message, $context);
    }

    public function error(string $category, string $message, array $context = []): void
    {
        $this->log('error', $category, $message, $context);
    }

    public function critical(string $category, string $message, array $context = []): void
    {
        $this->log('critical', $category, $message, $context);
    }

    private function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = $this->redact($value);
                continue;
            }

            foreach (self::REDACTED_KEYS as $sensitive) {
                if (stripos((string) $key, $sensitive) !== false) {
                    $context[$key] = '***REDACTED***';
                    break;
                }
            }
        }

        return $context;
    }
}
