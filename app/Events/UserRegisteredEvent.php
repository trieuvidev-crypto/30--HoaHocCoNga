<?php

declare(strict_types=1);

namespace App\Events;

use App\Core\Events\Event;

final class UserRegisteredEvent extends Event
{
    public function __construct(
        public readonly int $userId,
        public readonly string $userUuid,
        public readonly string $email
    ) {
        parent::__construct();
    }
}
