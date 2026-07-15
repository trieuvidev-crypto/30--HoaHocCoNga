<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Policies\CoursePolicy;
use App\Repositories\ChapterRepository;
use App\Repositories\CourseRepository;
use RuntimeException;

final class ChapterService
{
    public function __construct(
        private readonly ChapterRepository $chapters,
        private readonly CourseRepository $courses,
        private readonly CoursePolicy $policy
    ) {
    }

    public function create(int $userId, string $courseUuid, string $title, ?string $description): array
    {
        $course = $this->requireManageableCourse($userId, $courseUuid);

        if (trim($title) === '') {
            throw new RuntimeException('Tên chương không được để trống.');
        }

        $sortOrder = $this->chapters->nextSortOrder((int) $course['id']);

        return $this->chapters->create((int) $course['id'], trim($title), $description, $sortOrder);
    }

    public function update(int $userId, string $courseUuid, int $chapterId, array $input): array
    {
        $this->requireManageableCourse($userId, $courseUuid);
        $this->requireChapterBelongsToCourse($chapterId, $courseUuid);

        return $this->chapters->update($chapterId, $input);
    }

    public function reorder(int $userId, string $courseUuid, int $chapterId, int $newSortOrder): void
    {
        $this->requireManageableCourse($userId, $courseUuid);
        $this->requireChapterBelongsToCourse($chapterId, $courseUuid);

        $this->chapters->reorder($chapterId, $newSortOrder);
    }

    public function delete(int $userId, string $courseUuid, int $chapterId): void
    {
        $this->requireManageableCourse($userId, $courseUuid);
        $this->requireChapterBelongsToCourse($chapterId, $courseUuid);

        $this->chapters->softDelete($chapterId);
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCourse(string $courseUuid): array
    {
        $course = $this->courses->findByUuid($courseUuid);

        if ($course === null) {
            throw new RuntimeException('Không tìm thấy khóa học.');
        }

        return $this->chapters->findByCourse((int) $course['id']);
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

    private function requireChapterBelongsToCourse(int $chapterId, string $courseUuid): array
    {
        $chapter = $this->chapters->findById($chapterId);
        $course = $this->courses->findByUuid($courseUuid);

        if ($chapter === null || $course === null || (int) $chapter['course_id'] !== (int) $course['id']) {
            throw new RuntimeException('Chương không thuộc khóa học này.');
        }

        return $chapter;
    }
}
