<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Core\Logger;
use App\Events\UserRegisteredEvent;

/**
 * Reacts to a new registration by writing an audit-trail log entry.
 * Future listeners on the same event (Phase 3+): send verification
 * email, award a "First Login" achievement, notify the admin realtime
 * dashboard room — each added as its own listener, never by editing
 * AuthService.
 */
final class LogUserRegisteredListener implements ListenerInterface
{
    public function __construct(private readonly Logger $logger)
    {
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof UserRegisteredEvent) {
            return;
        }

        $this->logger->info('activity', 'Người dùng mới đăng ký.', [
            'user_id' => $event->userId,
            'user_uuid' => $event->userUuid,
        ]);
    }
}
