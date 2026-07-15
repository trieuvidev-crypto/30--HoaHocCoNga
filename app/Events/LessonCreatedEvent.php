<?php

declare(strict_types=1);

namespace App\Events;

use App\Core\Events\Event;

final class LessonCreatedEvent extends Event
{
    public function __construct(
        public readonly int $lessonId,
        public readonly string $lessonUuid,
        public readonly string $lessonTitle,
        public readonly int $courseId,
        public readonly string $courseTitle,
        public readonly bool $courseIsPublished
    ) {
        parent::__construct();
    }
}
