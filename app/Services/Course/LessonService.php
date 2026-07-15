<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Core\Events\EventDispatcher;
use App\Core\SlugGenerator;
use App\Events\LessonCreatedEvent;
use App\Policies\CoursePolicy;
use App\Repositories\ChapterRepository;
use App\Repositories\CourseRepository;
use App\Repositories\LessonRepository;
use RuntimeException;

final class LessonService
{
    public function __construct(
        private readonly LessonRepository $lessons,
        private readonly ChapterRepository $chapters,
        private readonly CourseRepository $courses,
        private readonly CoursePolicy $policy,
        private readonly EventDispatcher $events
    ) {
    }

    public function create(int $userId, string $courseUuid, int $chapterId, array $input): array
    {
        $course = $this->requireManageableCourse($userId, $courseUuid);
        $chapter = $this->requireChapterBelongsToCourse($chapterId, (int) $course['id']);

        $title = trim((string) ($input['title'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('Tên bài học không được để trống.');
        }

        $baseSlug = SlugGenerator::generate($title);
        $slug = SlugGenerator::unique(
            $baseSlug,
            fn (string $s) => $this->lessons->slugExistsInCourse((int) $course['id'], $s)
        );

        $sortOrder = $this->lessons->nextSortOrder((int) $chapter['id']);

        $lesson = $this->lessons->create([
            'course_id' => $course['id'],
            'chapter_id' => $chapter['id'],
            'title' => $title,
            'slug' => $slug,
            'summary' => $input['summary'] ?? null,
            'content_type' => $input['content_type'] ?? 'video',
            'content_body' => $input['content_body'] ?? null,
            'sort_order' => $sortOrder,
            'is_preview' => !empty($input['is_preview']) ? 1 : 0,
            'estimated_duration_minutes' => $input['estimated_duration_minutes'] ?? null,
            'created_by' => $userId,
        ]);

        $this->events->dispatch(new LessonCreatedEvent(
            (int) $lesson['id'],
            $lesson['uuid'],
            $lesson['title'],
            (int) $course['id'],
            $course['title'],
            $course['status'] === 'published'
        ));

        return $lesson;
    }

    public function update(int $userId, string $courseUuid, int $lessonId, array $input): array
    {
        $course = $this->requireManageableCourse($userId, $courseUuid);
        $this->requireLessonBelongsToCourse($lessonId, (int) $course['id']);

        return $this->lessons->update($lessonId, $input, $userId);
    }

    public function reorder(int $userId, string $courseUuid, int $lessonId, int $newSortOrder): void
    {
        $course = $this->requireManageableCourse($userId, $courseUuid);
        $this->requireLessonBelongsToCourse($lessonId, (int) $course['id']);

        $this->lessons->reorder($lessonId, $newSortOrder);
    }

    public function delete(int $userId, string $courseUuid, int $lessonId): void
    {
        $course = $this->requireManageableCourse($userId, $courseUuid);
        $this->requireLessonBelongsToCourse($lessonId, (int) $course['id']);

        $this->lessons->softDelete($lessonId);
    }

    /** @return array<int, array<string, mixed>> */
    public function listForChapter(int $chapterId): array
    {
        return $this->lessons->findByChapter($chapterId);
    }

    private function requireManageableCourse(int $userId, string $courseUuid): array
    {
        $course = $this->courses->findByUuid($courseUuid);

        if ($course === null) {
            throw new RuntimeException('Không tìm thấy khóa học.');
        }

        $assistantIds = $this->courses->getAssistantTeacherIds((int) $course['id']);

        if (!$this->policy->canManage($userId, $course, $assistantIds)) {
            throw new RuntimeException('Bạn không có quyền chỉnh sửa khóa học này.');
        }

        return $course;
    }

    private function requireChapterBelongsToCourse(int $chapterId, int $courseId): array
    {
        $chapter = $this->chapters->findById($chapterId);

        if ($chapter === null || (int) $chapter['course_id'] !== $courseId) {
            throw new RuntimeException('Chương không thuộc khóa học này.');
        }

        return $chapter;
    }

    private function requireLessonBelongsToCourse(int $lessonId, int $courseId): array
    {
        $lesson = $this->lessons->findById($lessonId);

        if ($lesson === null || (int) $lesson['course_id'] !== $courseId) {
            throw new RuntimeException('Bài học không thuộc khóa học này.');
        }

        return $lesson;
    }
}
