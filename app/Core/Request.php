<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $query;
    private array $body;
    private array $server;
    private array $files;
    private array $headers;
    private ?array $jsonBody = null;

    public function __construct(array $query, array $body, array $server, array $files)
    {
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->files = $files;
        $this->headers = $this->extractHeaders($server);
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES);
    }

    public function method(): string
    {
        $override = $this->headers['X-Http-Method-Override'] ?? null;

        return strtoupper($override ?? $this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return '/' . trim($path, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->allInput()[$key] ?? $default;
    }

    public function allInput(): array
    {
        if ($this->isJson()) {
            return $this->jsonBody ??= (json_decode(file_get_contents('php://input') ?: '[]', true) ?: []);
        }

        return array_merge($this->query, $this->body);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');

        if (is_string($header) && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');

        return is_string($contentType) && str_contains($contentType, 'application/json');
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function files(): array
    {
        return $this->files;
    }

    private function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = $server['CONTENT_TYPE'];
        }

        return $headers;
    }
}
