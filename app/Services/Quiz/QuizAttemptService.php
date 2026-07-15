<?php

declare(strict_types=1);

namespace App\Services\Quiz;

use App\Repositories\QuestionRepository;
use App\Repositories\QuizAttemptRepository;
use App\Repositories\QuizRepository;
use RuntimeException;

/**
 * The auto-grading core of the Quiz Engine. Grading rules (deliberately
 * simple and documented, not hidden):
 *   - single_choice/true_false: full points if the one selected option
 *     is exactly the correct one, else 0. No partial credit.
 *   - multiple_choice: full points only if the selected set exactly
 *     equals the correct set (no partial credit for partially-correct
 *     selections — a stricter but unambiguous rule).
 *   - fill_blank/calculation: exact match (case-insensitive, trimmed)
 *     against question_bank.text_answer. This does not understand
 *     numeric equivalence (e.g. "2" vs "2.0") — documented limitation,
 *     not silently pretended to be smarter than it is.
 *   - essay: never auto-graded; is_correct/points_earned stay NULL until
 *     a teacher grades it manually (not built in this phase).
 */
final class QuizAttemptService
{
    public function __construct(
        private readonly QuizRepository $quizzes,
        private readonly QuestionRepository $questions,
        private readonly QuizAttemptRepository $attempts
    ) {
    }

    /**
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function start(int $studentId, string $quizUuid): array
    {
        $quiz = $this->quizzes->findByUuid($quizUuid);

        if ($quiz === null || $quiz['status'] !== 'published') {
            throw new RuntimeException('Bài quiz không tồn tại hoặc chưa được xuất bản.');
        }

        $existing = $this->attempts->findInProgressAttempt((int) $quiz['id'], $studentId);

        if ($existing !== null) {
            return $this->attemptWithQuestions($existing, $quiz);
        }

        $attemptCount = $this->attempts->countAttemptsByStudent((int) $quiz['id'], $studentId);

        if ($quiz['max_attempts'] !== null && $attemptCount >= (int) $quiz['max_attempts']) {
            throw new RuntimeException('Bạn đã hết số lần làm bài cho phép với bài quiz này.');
        }

        if ($this->quizzes->countQuestions((int) $quiz['id']) === 0) {
            throw new RuntimeException('Bài quiz chưa có câu hỏi nào.');
        }

        $expiresAt = $quiz['time_limit_minutes'] !== null
            ? date('Y-m-d H:i:s', time() + (int) $quiz['time_limit_minutes'] * 60)
            : null;

        $attempt = $this->attempts->create((int) $quiz['id'], $studentId, $attemptCount + 1, $expiresAt);

        return $this->attemptWithQuestions($attempt, $quiz);
    }

    /**
     * @param array<int, int>|null $selectedOptionIds
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function submitAnswer(
        int $studentId,
        string $attemptUuid,
        string $questionUuid,
        ?array $selectedOptionIds,
        ?string $textAnswer
    ): void {
        $attempt = $this->requireOwnAttempt($attemptUuid, $studentId);
        $this->assertNotExpired($attempt);

        $question = $this->questions->findByUuid($questionUuid);

        if ($question === null) {
            throw new RuntimeException('Không tìm thấy câu hỏi.');
        }

        $points = $this->quizzes->getQuestionPoints((int) $attempt['quiz_id'], (int) $question['id']);

        if ($points === null) {
            throw new RuntimeException('Câu hỏi này không thuộc bài quiz đang làm.');
        }

        [$isCorrect, $pointsEarned] = $this->gradeAnswer($question, $selectedOptionIds, $textAnswer, $points);

        $this->attempts->upsertAnswer(
            (int) $attempt['id'],
            (int) $question['id'],
            $selectedOptionIds,
            $textAnswer,
            $isCorrect,
            $pointsEarned
        );
    }

    /**
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function finish(int $studentId, string $attemptUuid): array
    {
        $attempt = $this->requireOwnAttempt($attemptUuid, $studentId);

        if ($attempt['status'] !== 'in_progress') {
            throw new RuntimeException('Bài làm này đã được nộp trước đó.');
        }

        if ($this->isExpired($attempt)) {
            $this->attempts->markExpired((int) $attempt['id']);
            throw new RuntimeException('Đã hết thời gian làm bài. Bài làm được ghi nhận là hết hạn.');
        }

        $quiz = $this->quizzes->findById((int) $attempt['quiz_id']);
        $totalPoints = $this->quizzes->totalPoints((int) $attempt['quiz_id']);

        $answers = $this->attempts->findAnswers((int) $attempt['id']);
        $earnedPoints = array_sum(array_map(fn ($a) => (float) ($a['points_earned'] ?? 0), $answers));

        $scorePercent = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0.0;
        $isPassed = $quiz['passing_score'] !== null ? $scorePercent >= (float) $quiz['passing_score'] : null;

        return $this->attempts->finish((int) $attempt['id'], $totalPoints, $earnedPoints, $scorePercent, $isPassed);
    }

    /** @return array<int, array<string, mixed>> */
    public function history(int $studentId, string $quizUuid): array
    {
        $quiz = $this->quizzes->findByUuid($quizUuid);

        if ($quiz === null) {
            throw new RuntimeException('Không tìm thấy bài quiz.');
        }

        return $this->attempts->findHistoryForStudent((int) $quiz['id'], $studentId);
    }

    /**
     * @param array<int, int>|null $selectedOptionIds
     * @return array{0: ?bool, 1: ?float}
     */
    private function gradeAnswer(array $question, ?array $selectedOptionIds, ?string $textAnswer, float $points): array
    {
        $type = $question['question_type'];

        if (in_array($type, ['single_choice', 'multiple_choice', 'true_false'], true)) {
            $options = $this->questions->findOptions((int) $question['id']);
            $correctIds = array_values(array_map(
                fn ($o) => (int) $o['id'],
                array_filter($options, fn ($o) => (int) $o['is_correct'] === 1)
            ));

            $selected = array_values(array_unique(array_map('intval', $selectedOptionIds ?? [])));
            sort($correctIds);
            sort($selected);

            $isCorrect = $selected === $correctIds;

            return [$isCorrect, $isCorrect ? $points : 0.0];
        }

        if (in_array($type, ['fill_blank', 'calculation'], true)) {
            $expected = mb_strtolower(trim((string) $question['text_answer']));
            $actual = mb_strtolower(trim((string) $textAnswer));
            $isCorrect = $expected !== '' && $expected === $actual;

            return [$isCorrect, $isCorrect ? $points : 0.0];
        }

        // essay and any other free-form type: not auto-gradable.
        return [null, null];
    }

    private function attemptWithQuestions(array $attempt, array $quiz): array
    {
        $questions = $this->quizzes->findQuestionsForQuiz((int) $quiz['id']);

        // Never expose is_correct to the student while an attempt is in
        // progress — strip it from every option before returning.
        foreach ($questions as &$question) {
            $options = $this->questions->findOptions((int) $question['id']);
            $question['options'] = array_map(static function (array $option) {
                unset($option['is_correct']);

                return $option;
            }, $options);
        }
        unset($question);

        $attempt['questions'] = $questions;
        $attempt['quiz'] = $quiz;

        return $attempt;
    }

    private function requireOwnAttempt(string $attemptUuid, int $studentId): array
    {
        $attempt = $this->attempts->findByUuid($attemptUuid);

        if ($attempt === null || (int) $attempt['student_id'] !== $studentId) {
            throw new RuntimeException('Không tìm thấy bài làm.');
        }

        return $attempt;
    }

    private function assertNotExpired(array $attempt): void
    {
        if ($this->isExpired($attempt)) {
            $this->attempts->markExpired((int) $attempt['id']);

            throw new RuntimeException('Đã hết thời gian làm bài.');
        }
    }

    private function isExpired(array $attempt): bool
    {
        return $attempt['expires_at'] !== null && $attempt['expires_at'] < date('Y-m-d H:i:s');
    }
}
