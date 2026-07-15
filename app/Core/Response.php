<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private int $status = 200;
    private array $headers = [];
    private string $body = '';

    public static function view(string $html, int $status = 200): self
    {
        $response = new self();
        $response->status = $status;
        $response->headers['Content-Type'] = 'text/html; charset=UTF-8';
        $response->body = $html;

        return $response;
    }

    /**
     * Unified API success envelope, per API_ENDPOINTS.md response format.
     */
    public static function apiSuccess(mixed $data = null, string $message = '', array $meta = [], int $status = 200): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'errors' => null,
            'timestamp' => gmdate('c'),
            'request_id' => self::requestId(),
        ], $status);
    }

    /**
     * Unified API failure envelope. Never carries SQL text, stack traces,
     * or internal paths — only a stable error_code and safe messages.
     */
    public static function apiError(string $message, array $errors = [], string $errorCode = 'ERROR', int $status = 400): self
    {
        return self::json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'error_code' => $errorCode,
            'timestamp' => gmdate('c'),
            'request_id' => self::requestId(),
        ], $status);
    }

    public static function json(array $payload, int $status = 200): self
    {
        $response = new self();
        $response->status = $status;
        $response->headers['Content-Type'] = 'application/json; charset=UTF-8';
        $response->body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $response;
    }

    public static function redirect(string $to, int $status = 302): self
    {
        $response = new self();
        $response->status = $status;
        $response->headers['Location'] = $to;

        return $response;
    }

    public function withHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        foreach ((array) config('security.headers') as $key => $value) {
            header("{$key}: {$value}");
        }

        echo $this->body;
    }

    private static function requestId(): string
    {
        static $id = null;

        return $id ??= bin2hex(random_bytes(8));
    }
}
