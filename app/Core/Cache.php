<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Filesystem-backed cache (storage/cache/). No Redis dependency, per
 * CLAUDE.md's cPanel hosting constraint. Used for homepage data,
 * categories, popular content, settings, permissions and menus per
 * DATABASE_INDEXING.md §Caching Strategy.
 *
 * Values are serialized with a TTL envelope; a stale/missing key
 * transparently falls through to the caller's $resolver callback via
 * remember(), which is the primary API the rest of the app should use.
 */
final class Cache
{
    private static ?Cache $instance = null;

    private string $directory;

    private function __construct()
    {
        $this->directory = storage_path('cache');

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->pathFor($key);

        if (!is_file($path)) {
            return $default;
        }

        $envelope = @unserialize((string) file_get_contents($path));

        if (!is_array($envelope) || !isset($envelope['expires_at'], $envelope['value'])) {
            return $default;
        }

        if ($envelope['expires_at'] !== 0 && $envelope['expires_at'] < time()) {
            @unlink($path);

            return $default;
        }

        return $envelope['value'];
    }

    public function put(string $key, mixed $value, int $ttlSeconds = 3600): void
    {
        $envelope = [
            'key' => $key,
            'value' => $value,
            'expires_at' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
        ];

        file_put_contents($this->pathFor($key), serialize($envelope), LOCK_EX);
    }

    public function forget(string $key): void
    {
        $path = $this->pathFor($key);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Forget every cached key sharing a prefix (e.g. "course:" invalidates
     * every cached course entry). Prefixes are hashed per-key at write
     * time, so this walks the directory rather than doing a direct lookup.
     */
    public function forgetPrefix(string $prefix): void
    {
        foreach (glob($this->directory . '/*.cache') ?: [] as $file) {
            $meta = @unserialize((string) file_get_contents($file));

            if (is_array($meta) && isset($meta['key']) && str_starts_with($meta['key'], $prefix)) {
                @unlink($file);
            }
        }
    }

    /**
     * The main API: return the cached value, or compute + store it via
     * $resolver if missing/expired.
     */
    public function remember(string $key, int $ttlSeconds, callable $resolver): mixed
    {
        $cached = $this->get($key, '__CACHE_MISS__');

        if ($cached !== '__CACHE_MISS__') {
            return $cached;
        }

        $value = $resolver();
        $this->put($key, $value, $ttlSeconds);

        return $value;
    }

    private function pathFor(string $key): string
    {
        return $this->directory . '/' . hash('sha256', $key) . '.cache';
    }
}
