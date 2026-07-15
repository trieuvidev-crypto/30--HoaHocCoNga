<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class CourseRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM courses WHERE id = :id AND deleted_at IS NULL',
            ['id' => $id]
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM courses WHERE slug = :slug AND status = 'published' AND visibility = 'public' AND deleted_at IS NULL",
            ['slug' => $slug]
        );
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM courses WHERE uuid = :uuid AND deleted_at IS NULL',
            ['uuid' => $uuid]
        );
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $row = $this->db->fetchOne(
                'SELECT id FROM courses WHERE slug = :slug AND id != :id AND deleted_at IS NULL',
                ['slug' => $slug, 'id' => $excludeId]
            );
        } else {
            $row = $this->db->fetchOne(
                'SELECT id FROM courses WHERE slug = :slug AND deleted_at IS NULL',
                ['slug' => $slug]
            );
        }

        return $row !== null;
    }

    public function create(array $attributes): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO courses
                (uuid, title, slug, short_description, description, course_type, category_id,
                 grade_id, subject_id, primary_teacher_id, difficulty, price, sale_price,
                 estimated_duration_minutes, language, has_certificate, visibility, status,
                 created_by, created_at, updated_at)
             VALUES
                (:uuid, :title, :slug, :short_description, :description, :course_type, :category_id,
                 :grade_id, :subject_id, :primary_teacher_id, :difficulty, :price, :sale_price,
                 :duration, :language, :has_certificate, :visibility, :status,
                 :created_by, NOW(), NOW())',
            [
                'uuid' => $uuid,
                'title' => $attributes['title'],
                'slug' => $attributes['slug'],
                'short_description' => $attributes['short_description'] ?? null,
                'description' => $attributes['description'] ?? null,
                'course_type' => $attributes['course_type'] ?? 'paid',
                'category_id' => $attributes['category_id'] ?? null,
                'grade_id' => $attributes['grade_id'] ?? null,
                'subject_id' => $attributes['subject_id'] ?? null,
                'primary_teacher_id' => $attributes['primary_teacher_id'],
                'difficulty' => $attributes['difficulty'] ?? 'beginner',
                'price' => $attributes['price'] ?? 0,
                'sale_price' => $attributes['sale_price'] ?? null,
                'duration' => $attributes['estimated_duration_minutes'] ?? null,
                'language' => $attributes['language'] ?? 'vi',
                'has_certificate' => $attributes['has_certificate'] ?? 1,
                'visibility' => $attributes['visibility'] ?? 'public',
                'status' => 'draft',
                'created_by' => $attributes['primary_teacher_id'],
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $courseId, array $attributes, int $updatedBy): array
    {
        $fields = [];
        $params = ['id' => $courseId, 'updated_by' => $updatedBy];

        $updatable = [
            'title', 'slug', 'short_description', 'description', 'category_id', 'grade_id',
            'subject_id', 'difficulty', 'price', 'sale_price', 'estimated_duration_minutes',
            'visibility', 'seo_title', 'seo_description',
        ];

        foreach ($updatable as $field) {
            if (array_key_exists($field, $attributes)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $attributes[$field];
            }
        }

        if (empty($fields)) {
            return $this->findById($courseId);
        }

        $fields[] = 'updated_by = :updated_by';
        $fields[] = 'updated_at = NOW()';

        $this->db->query(
            'UPDATE courses SET ' . implode(', ', $fields) . ' WHERE id = :id',
            $params
        );

        return $this->findById($courseId);
    }

    public function updateStatus(int $courseId, string $status, int $updatedBy): array
    {
        $publishedAt = $status === 'published' ? ', published_at = NOW()' : '';

        $this->db->query(
            "UPDATE courses SET status = :status, updated_by = :updated_by, updated_at = NOW() {$publishedAt} WHERE id = :id",
            ['status' => $status, 'updated_by' => $updatedBy, 'id' => $courseId]
        );

        return $this->findById($courseId);
    }

    public function softDelete(int $courseId, int $deletedBy): void
    {
        $this->db->query(
            'UPDATE courses SET deleted_at = NOW(), deleted_by = :deleted_by WHERE id = :id',
            ['deleted_by' => $deletedBy, 'id' => $courseId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findByTeacher(int $teacherId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM courses
             WHERE primary_teacher_id = :teacher_id AND deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            ['teacher_id' => $teacherId]
        );
    }

    /** @return array<int, int> assistant teacher user ids for a course */
    public function getAssistantTeacherIds(int $courseId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT teacher_id FROM course_assistants WHERE course_id = :course_id',
            ['course_id' => $courseId]
        );

        return array_map('intval', array_column($rows, 'teacher_id'));
    }

    public function incrementEnrollmentCount(int $courseId): void
    {
        $this->db->query(
            'UPDATE courses SET enrollment_count = enrollment_count + 1 WHERE id = :id',
            ['id' => $courseId]
        );
    }

    /** @return array<int, int> student user ids currently enrolled in a course */
    public function getEnrolledStudentIds(int $courseId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT student_id FROM course_enrollments WHERE course_id = :course_id',
            ['course_id' => $courseId]
        );

        return array_map('intval', array_column($rows, 'student_id'));
    }

    /**
     * Public course catalogue listing — published + public visibility only.
     * @return array<int, array<string, mixed>>
     */
    public function findPublished(?int $categoryId = null, ?int $gradeId = null, int $limit = 24, int $offset = 0): array
    {
        $conditions = ["status = 'published'", "visibility = 'public'", 'deleted_at IS NULL'];
        $params = [];

        if ($categoryId !== null) {
            $conditions[] = 'category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($gradeId !== null) {
            $conditions[] = 'grade_id = :grade_id';
            $params['grade_id'] = $gradeId;
        }

        $where = implode(' AND ', $conditions);

        return $this->db->fetchAll(
            "SELECT * FROM courses WHERE {$where} ORDER BY published_at DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countPublished(): int
    {
        return (int) ($this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM courses WHERE status = 'published' AND visibility = 'public' AND deleted_at IS NULL"
        )['total'] ?? 0);
    }

    /**
     * Dashboard "My Courses" list — enrolled courses joined with progress,
     * most recently accessed first so "Continue Learning" surfaces the
     * right course first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findEnrolledCoursesForStudent(int $studentId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, e.completion_percent, e.last_accessed_at, e.enrolled_at
             FROM course_enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             WHERE e.student_id = :student_id AND c.deleted_at IS NULL
             ORDER BY e.last_accessed_at DESC, e.enrolled_at DESC
             LIMIT {$limit}",
            ['student_id' => $studentId]
        );
    }
}
