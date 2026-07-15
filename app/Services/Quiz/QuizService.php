<?php

declare(strict_types=1);

namespace App\Services\Quiz;

use App\Core\SlugGenerator;
use App\Repositories\QuestionRepository;
use App\Repositories\QuizRepository;
use RuntimeException;

final class QuizService
{
    public function __construct(
        private readonly QuizRepository $quizzes,
        private readonly QuestionRepository $questions
    ) {
    }

    public function create(int $creatorId, array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('Tên bài quiz không được để trống.');
        }

        $baseSlug = SlugGenerator::generate($title);
        $slug = SlugGenerator::unique($baseSlug, fn (string $s) => $this->quizzes->slugExists($s));

        $input['slug'] = $slug;

        return $this->quizzes->create($input, $creatorId);
    }

    public function addQuestion(string $quizUuid, string $questionUuid, ?float $points = null): array
    {
        $quiz = $this->requireQuiz($quizUuid);
        $question = $this->questions->findByUuid($questionUuid);

        if ($question === null) {
            throw new RuntimeException('Không tìm thấy câu hỏi.');
        }

        $sortOrder = $this->quizzes->nextQuestionSortOrder((int) $quiz['id']);
        $this->quizzes->addQuestion(
            (int) $quiz['id'],
            (int) $question['id'],
            $points ?? (float) $question['default_points'],
            $sortOrder
        );

        return $quiz;
    }

    public function removeQuestion(string $quizUuid, string $questionUuid): void
    {
        $quiz = $this->requireQuiz($quizUuid);
        $question = $this->questions->findByUuid($questionUuid);

        if ($question === null) {
            throw new RuntimeException('Không tìm thấy câu hỏi.');
        }

        $this->quizzes->removeQuestion((int) $quiz['id'], (int) $question['id']);
    }

    public function publish(string $quizUuid): array
    {
        $quiz = $this->requireQuiz($quizUuid);

        if ($this->quizzes->countQuestions((int) $quiz['id']) === 0) {
            throw new RuntimeException('Bài quiz cần có ít nhất một câu hỏi trước khi xuất bản.');
        }

        $this->quizzes->updateStatus((int) $quiz['id'], 'published');

        return $this->quizzes->findByUuid($quizUuid);
    }

    /** @return array<int, array<string, mixed>> */
    public function questionsForQuiz(string $quizUuid): array
    {
        $quiz = $this->requireQuiz($quizUuid);

        return $this->quizzes->findQuestionsForQuiz((int) $quiz['id']);
    }

    private function requireQuiz(string $uuid): array
    {
        $quiz = $this->quizzes->findByUuid($uuid);

        if ($quiz === null) {
            throw new RuntimeException('Không tìm thấy bài quiz.');
        }

        return $quiz;
    }
}
