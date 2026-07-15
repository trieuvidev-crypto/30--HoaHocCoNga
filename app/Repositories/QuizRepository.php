<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class QuizRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM quizzes WHERE id = :id AND deleted_at IS NULL',
            ['id' => $id]
        );
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM quizzes WHERE uuid = :uuid AND deleted_at IS NULL',
            ['uuid' => $uuid]
        );
    }

    public function slugExists(string $slug): bool
    {
        return $this->db->fetchOne('SELECT id FROM quizzes WHERE slug = :slug', ['slug' => $slug]) !== null;
    }

    public function create(array $attributes, int $creatorId): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO quizzes
                (uuid, title, slug, description, quiz_type, course_id, lesson_id, time_limit_minutes,
                 passing_score, shuffle_questions, shuffle_options, max_attempts, show_correct_answers,
                 status, created_by, created_at, updated_at)
             VALUES
                (:uuid, :title, :slug, :description, :quiz_type, :course_id, :lesson_id, :time_limit,
                 :passing_score, :shuffle_q, :shuffle_o, :max_attempts, :show_answers,
                 :status, :creator, NOW(), NOW())',
            [
                'uuid' => $uuid,
                'title' => $attributes['title'],
                'slug' => $attributes['slug'],
                'description' => $attributes['description'] ?? null,
                'quiz_type' => $attributes['quiz_type'] ?? 'practice',
                'course_id' => $attributes['course_id'] ?? null,
                'lesson_id' => $attributes['lesson_id'] ?? null,
                'time_limit' => $attributes['time_limit_minutes'] ?? null,
                'passing_score' => $attributes['passing_score'] ?? null,
                'shuffle_q' => !empty($attributes['shuffle_questions']) ? 1 : 0,
                'shuffle_o' => !empty($attributes['shuffle_options']) ? 1 : 0,
                'max_attempts' => $attributes['max_attempts'] ?? null,
                'show_answers' => $attributes['show_correct_answers'] ?? 'after_submit',
                'status' => 'draft',
                'creator' => $creatorId,
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function updateStatus(int $quizId, string $status): void
    {
        $this->db->query(
            'UPDATE quizzes SET status = :status, updated_at = NOW() WHERE id = :id',
            ['status' => $status, 'id' => $quizId]
        );
    }

    public function addQuestion(int $quizId, int $questionId, float $points, int $sortOrder): void
    {
        $this->db->query(
            'INSERT INTO quiz_questions (quiz_id, question_id, points, sort_order)
             VALUES (:quiz_id, :question_id, :points, :sort_order)
             ON DUPLICATE KEY UPDATE points = VALUES(points), sort_order = VALUES(sort_order)',
            ['quiz_id' => $quizId, 'question_id' => $questionId, 'points' => $points, 'sort_order' => $sortOrder]
        );
    }

    public function removeQuestion(int $quizId, int $questionId): void
    {
        $this->db->query(
            'DELETE FROM quiz_questions WHERE quiz_id = :quiz_id AND question_id = :question_id',
            ['quiz_id' => $quizId, 'question_id' => $questionId]
        );
    }

    public function nextQuestionSortOrder(int $quizId): int
    {
        $max = $this->db->fetchOne(
            'SELECT MAX(sort_order) AS max_order FROM quiz_questions WHERE quiz_id = :quiz_id',
            ['quiz_id' => $quizId]
        );

        return ((int) ($max['max_order'] ?? 0)) + 1;
    }

    /** @return array<int, array<string, mixed>> quiz_questions joined with question_bank */
    public function findQuestionsForQuiz(int $quizId): array
    {
        return $this->db->fetchAll(
            'SELECT qq.points, qq.sort_order, qb.*
             FROM quiz_questions qq
             INNER JOIN question_bank qb ON qb.id = qq.question_id
             WHERE qq.quiz_id = :quiz_id AND qb.deleted_at IS NULL
             ORDER BY qq.sort_order ASC',
            ['quiz_id' => $quizId]
        );
    }

    public function countQuestions(int $quizId): int
    {
        return (int) ($this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM quiz_questions WHERE quiz_id = :quiz_id',
            ['quiz_id' => $quizId]
        )['total'] ?? 0);
    }

    public function getQuestionPoints(int $quizId, int $questionId): ?float
    {
        $row = $this->db->fetchOne(
            'SELECT points FROM quiz_questions WHERE quiz_id = :quiz_id AND question_id = :question_id',
            ['quiz_id' => $quizId, 'question_id' => $questionId]
        );

        return $row !== null ? (float) $row['points'] : null;
    }

    public function totalPoints(int $quizId): float
    {
        return (float) ($this->db->fetchOne(
            'SELECT COALESCE(SUM(points), 0) AS total FROM quiz_questions WHERE quiz_id = :quiz_id',
            ['quiz_id' => $quizId]
        )['total'] ?? 0.0);
    }
}
