<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Repositories\CourseRepository;
use App\Events\LessonCreatedEvent;
use App\Services\Notification\NotificationService;

/**
 * Per PROJECT.md §Realtime Course ("Lesson Released → Students immediately
 * receive updates"): whenever a teacher adds a lesson to a course that is
 * already published, every currently enrolled student gets an in-app +
 * (if enabled) email notification. Draft-course lessons don't notify
 * anyone, since nobody is enrolled in an unpublished course yet.
 */
final class NotifyEnrolledStudentsListener implements ListenerInterface
{
    public function __construct(
        private readonly CourseRepository $courses,
        private readonly NotificationService $notifications
    ) {
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof LessonCreatedEvent || !$event->courseIsPublished) {
            return;
        }

        $studentIds = $this->courses->getEnrolledStudentIds($event->courseId);

        foreach ($studentIds as $studentId) {
            $this->notifications->notify(
                userId: $studentId,
                category: 'lesson',
                keyName: 'lesson.released',
                title: 'Bài học mới: {{lesson_title}}',
                body: 'Khóa học "{{course_title}}" vừa có bài học mới. Vào học ngay để không bỏ lỡ!',
                actionUrl: rtrim((string) config('app.url'), '/') . '/course/lesson/' . $event->lessonUuid,
                variables: [
                    'lesson_title' => $event->lessonTitle,
                    'course_title' => $event->courseTitle,
                ],
                priority: 'normal'
            );
        }
    }
}
