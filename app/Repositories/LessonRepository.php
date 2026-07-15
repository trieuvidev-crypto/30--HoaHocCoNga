<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class LessonRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM course_lessons WHERE id = :id AND deleted_at IS NULL',
            ['id' => $id]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findByChapter(int $chapterId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM course_lessons WHERE chapter_id = :chapter_id AND deleted_at IS NULL ORDER BY sort_order ASC',
            ['chapter_id' => $chapterId]
        );
    }

    public function slugExistsInCourse(int $courseId, string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM course_lessons WHERE course_id = :course_id AND slug = :slug AND deleted_at IS NULL';
        $params = ['course_id' => $courseId, 'slug' => $slug];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        return $this->db->fetchOne($sql, $params) !== null;
    }

    public function create(array $attributes): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO course_lessons
                (uuid, course_id, chapter_id, title, slug, summary, content_type, content_body,
                 sort_order, is_preview, estimated_duration_minutes, created_by, updated_by,
                 created_at, updated_at)
             VALUES
                (:uuid, :course_id, :chapter_id, :title, :slug, :summary, :content_type, :content_body,
                 :sort_order, :is_preview, :duration, :created_by, :updated_by,
                 NOW(), NOW())',
            [
                'uuid' => $uuid,
                'course_id' => $attributes['course_id'],
                'chapter_id' => $attributes['chapter_id'],
                'title' => $attributes['title'],
                'slug' => $attributes['slug'],
                'summary' => $attributes['summary'] ?? null,
                'content_type' => $attributes['content_type'] ?? 'video',
                'content_body' => $attributes['content_body'] ?? null,
                'sort_order' => $attributes['sort_order'],
                'is_preview' => $attributes['is_preview'] ?? 0,
                'duration' => $attributes['estimated_duration_minutes'] ?? null,
                'created_by' => $attributes['created_by'],
                'updated_by' => $attributes['created_by'],
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $lessonId, array $attributes, int $updatedBy): array
    {
        $fields = [];
        $params = ['id' => $lessonId, 'updated_by' => $updatedBy];

        foreach (['title', 'slug', 'summary', 'content_type', 'content_body', 'is_preview', 'is_visible', 'release_at', 'estimated_duration_minutes', 'video_media_id', 'video_duration_seconds'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $attributes[$field];
            }
        }

        if (empty($fields)) {
            return $this->findById($lessonId);
        }

        $fields[] = 'updated_by = :updated_by';
        $fields[] = 'updated_at = NOW()';

        $this->db->query('UPDATE course_lessons SET ' . implode(', ', $fields) . ' WHERE id = :id', $params);

        return $this->findById($lessonId);
    }

    public function reorder(int $lessonId, int $sortOrder): void
    {
        $this->db->query(
            'UPDATE course_lessons SET sort_order = :sort_order, updated_at = NOW() WHERE id = :id',
            ['sort_order' => $sortOrder, 'id' => $lessonId]
        );
    }

    public function softDelete(int $lessonId): void
    {
        $this->db->query(
            'UPDATE course_lessons SET deleted_at = NOW() WHERE id = :id',
            ['id' => $lessonId]
        );
    }

    public function nextSortOrder(int $chapterId): int
    {
        $max = $this->db->fetchOne(
            'SELECT MAX(sort_order) AS max_order FROM course_lessons WHERE chapter_id = :chapter_id AND deleted_at IS NULL',
            ['chapter_id' => $chapterId]
        );

        return ((int) ($max['max_order'] ?? 0)) + 1;
    }
}
