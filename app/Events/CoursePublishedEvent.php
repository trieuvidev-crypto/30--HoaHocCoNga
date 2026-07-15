<?php

declare(strict_types=1);

namespace App\Events;

use App\Core\Events\Event;

final class CoursePublishedEvent extends Event
{
    public function __construct(public readonly int $courseId, public readonly string $courseUuid, public readonly string $courseTitle)
    {
        parent::__construct();
    }
}
