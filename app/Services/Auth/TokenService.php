<?php

declare(strict_types=1);

namespace App\Services\Auth;

/**
 * Minimal, dependency-free JWT (HS256) issuance and validation.
 * Used for API authentication and Socket.IO handshake auth. Session
 * auth (for server-rendered pages) does not use this service.
 */
final class TokenService
{
    public function issueAccessToken(int $userId, string $userUuid, array $extraClaims = []): string
    {
        $ttl = (int) config('security.jwt.ttl_minutes', 60) * 60;

        return $this->encode(array_merge([
            'sub' => $userId,
            'uuid' => $userUuid,
            'iss' => config('security.jwt.issuer'),
            'iat' => time(),
            'exp' => time() + $ttl,
            'type' => 'access',
        ], $extraClaims));
    }

    public function issueRefreshToken(int $userId, string $userUuid): string
    {
        $ttl = (int) config('security.jwt.refresh_ttl_days', 14) * 86400;

        return $this->encode([
            'sub' => $userId,
            'uuid' => $userUuid,
            'iss' => config('security.jwt.issuer'),
            'iat' => time(),
            'exp' => time() + $ttl,
            'type' => 'refresh',
        ]);
    }

    /**
     * Returns the decoded payload if the token is structurally valid,
     * correctly signed, and not expired — otherwise null. Callers must
     * treat null as "reject the request", never fall back to a default.
     */
    public function validateAccessToken(string $token): ?array
    {
        $payload = $this->decode($token);

        if ($payload === null || ($payload['type'] ?? null) !== 'access') {
            return null;
        }

        return $payload;
    }

    public function validateRefreshToken(string $token): ?array
    {
        $payload = $this->decode($token);

        if ($payload === null || ($payload['type'] ?? null) !== 'refresh') {
            return null;
        }

        return $payload;
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $signature = $this->sign("{$header}.{$body}");

        return "{$header}.{$body}.{$signature}";
    }

    private function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;

        if (!hash_equals($this->sign("{$header}.{$body}"), $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($body), true);

        if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private function sign(string $data): string
    {
        $secret = config('security.jwt.secret', '');

        return $this->base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + (4 - strlen($data) % 4), '=');

        return base64_decode($padded) ?: '';
    }
}
