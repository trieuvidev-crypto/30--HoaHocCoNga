<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class QuestionRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM question_bank WHERE id = :id AND deleted_at IS NULL',
            ['id' => $id]
        );
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM question_bank WHERE uuid = :uuid AND deleted_at IS NULL',
            ['uuid' => $uuid]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findOptions(int $questionId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM question_options WHERE question_id = :question_id ORDER BY sort_order ASC',
            ['question_id' => $questionId]
        );
    }

    public function create(array $attributes, int $creatorId): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO question_bank
                (uuid, question_type, title, explanation, hint, difficulty, grade_id, subject_id,
                 text_answer, default_points, status, created_by, updated_by, created_at, updated_at)
             VALUES
                (:uuid, :type, :title, :explanation, :hint, :difficulty, :grade_id, :subject_id,
                 :text_answer, :points, :status, :creator, :updater, NOW(), NOW())',
            [
                'uuid' => $uuid,
                'type' => $attributes['question_type'],
                'title' => $attributes['title'],
                'explanation' => $attributes['explanation'] ?? null,
                'hint' => $attributes['hint'] ?? null,
                'difficulty' => $attributes['difficulty'] ?? 'medium',
                'grade_id' => $attributes['grade_id'] ?? null,
                'subject_id' => $attributes['subject_id'] ?? null,
                'text_answer' => $attributes['text_answer'] ?? null,
                'points' => $attributes['default_points'] ?? 1.0,
                'status' => $attributes['status'] ?? 'draft',
                'creator' => $creatorId,
                'updater' => $creatorId,
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    /**
     * @param array<int, array{content: string, is_correct: bool}> $options
     */
    public function replaceOptions(int $questionId, array $options): void
    {
        $this->db->query('DELETE FROM question_options WHERE question_id = :question_id', ['question_id' => $questionId]);

        foreach ($options as $index => $option) {
            $this->db->query(
                'INSERT INTO question_options (question_id, content, is_correct, sort_order)
                 VALUES (:question_id, :content, :is_correct, :sort_order)',
                [
                    'question_id' => $questionId,
                    'content' => $option['content'],
                    'is_correct' => !empty($option['is_correct']) ? 1 : 0,
                    'sort_order' => $index,
                ]
            );
        }
    }

    public function updateStatus(int $questionId, string $status): void
    {
        $this->db->query(
            'UPDATE question_bank SET status = :status, updated_at = NOW() WHERE id = :id',
            ['status' => $status, 'id' => $questionId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $keyword, ?int $gradeId, ?string $type, ?string $difficulty, int $limit = 50): array
    {
        $conditions = ['deleted_at IS NULL'];
        $params = [];

        if ($keyword !== null && $keyword !== '') {
            $conditions[] = 'MATCH(title) AGAINST(:keyword IN NATURAL LANGUAGE MODE)';
            $params['keyword'] = $keyword;
        }

        if ($gradeId !== null) {
            $conditions[] = 'grade_id = :grade_id';
            $params['grade_id'] = $gradeId;
        }

        if ($type !== null) {
            $conditions[] = 'question_type = :type';
            $params['type'] = $type;
        }

        if ($difficulty !== null) {
            $conditions[] = 'difficulty = :difficulty';
            $params['difficulty'] = $difficulty;
        }

        $where = implode(' AND ', $conditions);

        return $this->db->fetchAll(
            "SELECT * FROM question_bank WHERE {$where} ORDER BY created_at DESC LIMIT {$limit}",
            $params
        );
    }
}
