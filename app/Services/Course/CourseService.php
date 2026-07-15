<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Core\Cache;
use App\Core\Events\EventDispatcher;
use App\Core\SlugGenerator;
use App\Events\CourseCreatedEvent;
use App\Events\CoursePublishedEvent;
use App\Policies\CoursePolicy;
use App\Repositories\CourseRepository;
use RuntimeException;

final class CourseService
{
    public function __construct(
        private readonly CourseRepository $courses,
        private readonly CoursePolicy $policy,
        private readonly EventDispatcher $events,
        private readonly Cache $cache
    ) {
    }

    /**
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function create(int $teacherId, array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('Tên khóa học không được để trống.');
        }

        $baseSlug = SlugGenerator::generate($title);

        if ($baseSlug === '') {
            throw new RuntimeException('Tên khóa học không hợp lệ để tạo đường dẫn.');
        }

        $slug = SlugGenerator::unique($baseSlug, fn (string $s) => $this->courses->slugExists($s));

        $course = $this->courses->create([
            'title' => $title,
            'slug' => $slug,
            'short_description' => $input['short_description'] ?? null,
            'description' => $input['description'] ?? null,
            'course_type' => $input['course_type'] ?? 'paid',
            'category_id' => $input['category_id'] ?? null,
            'grade_id' => $input['grade_id'] ?? null,
            'subject_id' => $input['subject_id'] ?? null,
            'primary_teacher_id' => $teacherId,
            'difficulty' => $input['difficulty'] ?? 'beginner',
            'price' => $input['price'] ?? 0,
            'sale_price' => $input['sale_price'] ?? null,
            'estimated_duration_minutes' => $input['estimated_duration_minutes'] ?? null,
        ]);

        $this->events->dispatch(new CourseCreatedEvent((int) $course['id'], $course['uuid'], $teacherId));
        $this->invalidateListingCache();

        return $course;
    }

    /**
     * @throws RuntimeException with a Vietnamese, user-safe message (validation
     * or 403-equivalent ownership failure — Controller maps the message to
     * the right HTTP status via a marker, see CourseController)
     */
    public function update(int $userId, string $courseUuid, array $input): array
    {
        $course = $this->requireCourse($courseUuid);
        $this->assertCanManage($userId, $course);

        if (isset($input['title'])) {
            $newSlugBase = SlugGenerator::generate((string) $input['title']);
            $input['slug'] = SlugGenerator::unique(
                $newSlugBase,
                fn (string $s) => $this->courses->slugExists($s, (int) $course['id'])
            );
        }

        $updated = $this->courses->update((int) $course['id'], $input, $userId);
        $this->invalidateListingCache();

        return $updated;
    }

    public function publish(int $userId, string $courseUuid): array
    {
        $course = $this->requireCourse($courseUuid);

        if (!$this->policy->canPublish($userId, $course)) {
            throw new RuntimeException('Bạn không có quyền xuất bản khóa học này.');
        }

        if ($course['status'] === 'published') {
            throw new RuntimeException('Khóa học đã được xuất bản trước đó.');
        }

        $this->assertReadyToPublish($course);

        $updated = $this->courses->updateStatus((int) $course['id'], 'published', $userId);

        $this->events->dispatch(new CoursePublishedEvent((int) $updated['id'], $updated['uuid'], $updated['title']));
        $this->invalidateListingCache();

        return $updated;
    }

    public function archive(int $userId, string $courseUuid): array
    {
        $course = $this->requireCourse($courseUuid);
        $this->assertCanManage($userId, $course);

        $updated = $this->courses->updateStatus((int) $course['id'], 'archived', $userId);
        $this->invalidateListingCache();

        return $updated;
    }

    /**
     * Duplicates a course's top-level record only (title suffixed "(Bản sao)",
     * reset to draft). Duplicating chapters/lessons is intentionally left to
     * ChapterService/LessonService once the caller has the new course id —
     * keeps this method's transaction small and each service owning only
     * its own aggregate, per MODULE_SYSTEM.md.
     */
    public function duplicate(int $userId, string $courseUuid): array
    {
        $course = $this->requireCourse($courseUuid);
        $this->assertCanManage($userId, $course);

        $newTitle = $course['title'] . ' (Bản sao)';
        $slug = SlugGenerator::unique(
            SlugGenerator::generate($newTitle),
            fn (string $s) => $this->courses->slugExists($s)
        );

        $duplicate = $this->courses->create([
            'title' => $newTitle,
            'slug' => $slug,
            'short_description' => $course['short_description'],
            'description' => $course['description'],
            'course_type' => $course['course_type'],
            'category_id' => $course['category_id'],
            'grade_id' => $course['grade_id'],
            'subject_id' => $course['subject_id'],
            'primary_teacher_id' => (int) $course['primary_teacher_id'],
            'difficulty' => $course['difficulty'],
            'price' => $course['price'],
            'sale_price' => $course['sale_price'],
            'estimated_duration_minutes' => $course['estimated_duration_minutes'],
        ]);

        $this->events->dispatch(new CourseCreatedEvent((int) $duplicate['id'], $duplicate['uuid'], (int) $course['primary_teacher_id']));
        $this->invalidateListingCache();

        return $duplicate;
    }

    public function delete(int $userId, string $courseUuid): void
    {
        $course = $this->requireCourse($courseUuid);

        // Deletion (even soft) is restricted to admins/primary teacher only —
        // same bar as publish, deliberately stricter than general editing.
        if (!$this->policy->canPublish($userId, $course)) {
            throw new RuntimeException('Bạn không có quyền xóa khóa học này.');
        }

        $this->courses->softDelete((int) $course['id'], $userId);
        $this->invalidateListingCache();
    }

    private function assertReadyToPublish(array $course): void
    {
        if (trim((string) $course['short_description']) === '') {
            throw new RuntimeException('Khóa học cần có mô tả ngắn trước khi xuất bản.');
        }
    }

    private function assertCanManage(int $userId, array $course): void
    {
        $assistantIds = $this->courses->getAssistantTeacherIds((int) $course['id']);

        if (!$this->policy->canManage($userId, $course, $assistantIds)) {
            throw new RuntimeException('Bạn không có quyền chỉnh sửa khóa học này.');
        }
    }

    private function requireCourse(string $uuid): array
    {
        $course = $this->courses->findByUuid($uuid);

        if ($course === null) {
            throw new RuntimeException('Không tìm thấy khóa học.');
        }

        return $course;
    }

    private function invalidateListingCache(): void
    {
        // Homepage/category listings cache popular & newest courses per
        // DATABASE_INDEXING.md §Caching Strategy; any create/update/publish
        // must invalidate so changes are visible without waiting out a TTL.
        $this->cache->forgetPrefix('courses:');
    }
}
