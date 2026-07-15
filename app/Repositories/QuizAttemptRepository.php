<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class QuizAttemptRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM quiz_attempts WHERE id = :id', ['id' => $id]);
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne('SELECT * FROM quiz_attempts WHERE uuid = :uuid', ['uuid' => $uuid]);
    }

    public function countAttemptsByStudent(int $quizId, int $studentId): int
    {
        return (int) ($this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM quiz_attempts WHERE quiz_id = :quiz_id AND student_id = :student_id',
            ['quiz_id' => $quizId, 'student_id' => $studentId]
        )['total'] ?? 0);
    }

    public function findInProgressAttempt(int $quizId, int $studentId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM quiz_attempts
             WHERE quiz_id = :quiz_id AND student_id = :student_id AND status = 'in_progress'
             ORDER BY id DESC LIMIT 1",
            ['quiz_id' => $quizId, 'student_id' => $studentId]
        );
    }

    public function create(int $quizId, int $studentId, int $attemptNumber, ?string $expiresAt): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO quiz_attempts (uuid, quiz_id, student_id, attempt_number, status, started_at, expires_at)
             VALUES (:uuid, :quiz_id, :student_id, :attempt_number, :status, NOW(), :expires_at)',
            [
                'uuid' => $uuid,
                'quiz_id' => $quizId,
                'student_id' => $studentId,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'expires_at' => $expiresAt,
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    /**
     * @param array<int, int>|null $selectedOptionIds
     */
    public function upsertAnswer(int $attemptId, int $questionId, ?array $selectedOptionIds, ?string $textAnswer, ?bool $isCorrect, ?float $pointsEarned): void
    {
        $this->db->query(
            'INSERT INTO quiz_attempt_answers (attempt_id, question_id, selected_option_ids, text_answer, is_correct, points_earned, answered_at)
             VALUES (:attempt_id, :question_id, :selected, :text_answer, :is_correct, :points, NOW())
             ON DUPLICATE KEY UPDATE
                selected_option_ids = VALUES(selected_option_ids),
                text_answer = VALUES(text_answer),
                is_correct = VALUES(is_correct),
                points_earned = VALUES(points_earned),
                answered_at = NOW()',
            [
                'attempt_id' => $attemptId,
                'question_id' => $questionId,
                'selected' => $selectedOptionIds !== null ? json_encode($selectedOptionIds) : null,
                'text_answer' => $textAnswer,
                'is_correct' => $isCorrect === null ? null : (int) $isCorrect,
                'points' => $pointsEarned,
            ]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findAnswers(int $attemptId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM quiz_attempt_answers WHERE attempt_id = :attempt_id',
            ['attempt_id' => $attemptId]
        );
    }

    public function finish(int $attemptId, float $totalPoints, float $earnedPoints, float $scorePercent, ?bool $isPassed): array
    {
        $this->db->query(
            "UPDATE quiz_attempts
             SET status = 'submitted', total_points = :total, earned_points = :earned,
                 score_percent = :score, is_passed = :passed, submitted_at = NOW()
             WHERE id = :id",
            [
                'total' => $totalPoints,
                'earned' => $earnedPoints,
                'score' => $scorePercent,
                'passed' => $isPassed === null ? null : (int) $isPassed,
                'id' => $attemptId,
            ]
        );

        return $this->findById($attemptId);
    }

    public function markExpired(int $attemptId): void
    {
        $this->db->query(
            "UPDATE quiz_attempts SET status = 'expired', submitted_at = NOW() WHERE id = :id",
            ['id' => $attemptId]
        );
    }

    /** @return array<int, array<string, mixed>> attempt history for a student on a quiz */
    public function findHistoryForStudent(int $quizId, int $studentId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM quiz_attempts WHERE quiz_id = :quiz_id AND student_id = :student_id ORDER BY attempt_number DESC',
            ['quiz_id' => $quizId, 'student_id' => $studentId]
        );
    }
}
