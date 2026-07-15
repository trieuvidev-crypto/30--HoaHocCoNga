<?php

declare(strict_types=1);

namespace App\Core\Events;

/**
 * Marker base class for all domain events (UserRegistered, CourseCreated,
 * PaymentCompleted, ...). Events are plain data carriers — no behavior.
 * See HOOK_EVENT_SYSTEM.md for the full catalogue this will grow to cover.
 */
abstract class Event
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
