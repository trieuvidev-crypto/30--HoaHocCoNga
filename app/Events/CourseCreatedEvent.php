<?php

declare(strict_types=1);

namespace App\Events;

use App\Core\Events\Event;

final class CourseCreatedEvent extends Event
{
    public function __construct(public readonly int $courseId, public readonly string $courseUuid, public readonly int $teacherId)
    {
        parent::__construct();
    }
}
