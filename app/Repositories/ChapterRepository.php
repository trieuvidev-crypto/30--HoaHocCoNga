<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ChapterRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM course_chapters WHERE id = :id AND deleted_at IS NULL',
            ['id' => $id]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findByCourse(int $courseId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM course_chapters WHERE course_id = :course_id AND deleted_at IS NULL ORDER BY sort_order ASC',
            ['course_id' => $courseId]
        );
    }

    public function create(int $courseId, string $title, ?string $description, int $sortOrder): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO course_chapters (uuid, course_id, title, description, sort_order, created_at, updated_at)
             VALUES (:uuid, :course_id, :title, :description, :sort_order, NOW(), NOW())',
            [
                'uuid' => $uuid,
                'course_id' => $courseId,
                'title' => $title,
                'description' => $description,
                'sort_order' => $sortOrder,
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $chapterId, array $attributes): array
    {
        $fields = [];
        $params = ['id' => $chapterId];

        foreach (['title', 'description', 'is_visible', 'release_at'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $attributes[$field];
            }
        }

        if (!empty($fields)) {
            $fields[] = 'updated_at = NOW()';
            $this->db->query('UPDATE course_chapters SET ' . implode(', ', $fields) . ' WHERE id = :id', $params);
        }

        return $this->findById($chapterId);
    }

    public function reorder(int $chapterId, int $sortOrder): void
    {
        $this->db->query(
            'UPDATE course_chapters SET sort_order = :sort_order, updated_at = NOW() WHERE id = :id',
            ['sort_order' => $sortOrder, 'id' => $chapterId]
        );
    }

    public function softDelete(int $chapterId): void
    {
        $this->db->query(
            'UPDATE course_chapters SET deleted_at = NOW() WHERE id = :id',
            ['id' => $chapterId]
        );
    }

    public function nextSortOrder(int $courseId): int
    {
        $max = $this->db->fetchOne(
            'SELECT MAX(sort_order) AS max_order FROM course_chapters WHERE course_id = :course_id AND deleted_at IS NULL',
            ['course_id' => $courseId]
        );

        return ((int) ($max['max_order'] ?? 0)) + 1;
    }
}
