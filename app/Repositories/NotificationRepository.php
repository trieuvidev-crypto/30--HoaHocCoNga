<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class NotificationRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function create(int $userId, string $category, string $priority, string $title, string $body, ?string $actionUrl = null, ?array $context = null): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO notifications (uuid, user_id, category, priority, title, body, action_url, context, created_at)
             VALUES (:uuid, :user_id, :category, :priority, :title, :body, :action_url, :context, NOW())',
            [
                'uuid' => $uuid,
                'user_id' => $userId,
                'category' => $category,
                'priority' => $priority,
                'title' => $title,
                'body' => $body,
                'action_url' => $actionUrl,
                'context' => $context !== null ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            ]
        );

        return $this->db->fetchOne('SELECT * FROM notifications WHERE uuid = :uuid', ['uuid' => $uuid]);
    }

    public function getPreference(int $userId, string $category): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM notification_preferences WHERE user_id = :user_id AND category = :category',
            ['user_id' => $userId, 'category' => $category]
        );
    }

    public function logDelivery(?int $userId, string $channel, string $keyName, string $status, ?string $errorMessage = null): void
    {
        $this->db->query(
            'INSERT INTO notification_logs (user_id, channel, key_name, status, error_message, created_at)
             VALUES (:user_id, :channel, :key_name, :status, :error_message, NOW())',
            [
                'user_id' => $userId,
                'channel' => $channel,
                'key_name' => $keyName,
                'status' => $status,
                'error_message' => $errorMessage,
            ]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function getUnreadForUser(int $userId, int $limit = 20): array
    {
        // $limit is a PHP int, not user-controlled raw input, so inlining it
        // here is safe — LIMIT bound as a string PDO param can fail under
        // native prepares (PDO::ATTR_EMULATE_PREPARES = false).
        return $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = :user_id AND read_at IS NULL
             ORDER BY created_at DESC LIMIT {$limit}",
            ['user_id' => $userId]
        );
    }

    public function markRead(int $notificationId, int $userId): void
    {
        $this->db->query(
            'UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :user_id AND read_at IS NULL',
            ['id' => $notificationId, 'user_id' => $userId]
        );
    }
}
