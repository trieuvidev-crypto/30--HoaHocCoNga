<?php

declare(strict_types=1);

namespace App\Services\Realtime;

use App\Core\Logger;

/**
 * The only way PHP code reaches Socket.IO — everything else in the app
 * (NotificationService, CourseService, etc.) calls one of the methods
 * here; nothing constructs an HTTP request to Node directly. Every call
 * is fire-and-forget: a slow or down Node process must never fail or
 * slow down the PHP request that triggered it (see PROJECT.md's
 * requirement that Node stays a lightweight, decoupled relay).
 */
final class RealtimeBroadcastService
{
    public function __construct(private readonly Logger $logger)
    {
    }

    public function toUser(string $userUuid, string $event, array $data = []): void
    {
        $this->post('/broadcast/user', ['userUuid' => $userUuid, 'event' => $event, 'data' => $data]);
    }

    public function toCourse(string $courseUuid, string $event, array $data = []): void
    {
        $this->post('/broadcast/course', ['courseUuid' => $courseUuid, 'event' => $event, 'data' => $data]);
    }

    public function toAdmin(string $event, array $data = []): void
    {
        $this->post('/broadcast/admin', ['event' => $event, 'data' => $data]);
    }

    private function post(string $path, array $payload): void
    {
        $secret = config('socket.internal_secret');

        if ($secret === '') {
            // Not configured — silently no-op rather than throwing, since
            // realtime delivery is an enhancement, not a hard dependency
            // for any business operation to succeed.
            return;
        }

        $url = rtrim(config('socket.internal_bridge_url'), '/') . $path;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Internal-Secret: {$secret}\r\n",
                'content' => $body,
                'timeout' => (float) config('socket.timeout_seconds', 2),
                'ignore_errors' => true,
            ],
        ]);

        try {
            // Suppressed: a Node outage must not surface as a warning to
            // the end user or interrupt the calling request. The failure
            // is still logged below via the false-return check.
            $result = @file_get_contents($url, false, $context);

            if ($result === false) {
                $this->logger->warning('realtime', 'Không thể gửi broadcast tới Node.js (server có thể đang tắt).', ['path' => $path]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('realtime', 'Lỗi khi gửi broadcast tới Node.js: ' . $e->getMessage(), ['path' => $path]);
        }
    }
}
