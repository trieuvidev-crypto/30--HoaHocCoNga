<?php

declare(strict_types=1);

namespace App\Services\Quiz;

use App\Repositories\QuestionRepository;
use RuntimeException;

final class QuestionService
{
    private const CHOICE_TYPES = ['single_choice', 'multiple_choice', 'true_false'];

    public function __construct(private readonly QuestionRepository $questions)
    {
    }

    /**
     * @param array<int, array{content: string, is_correct: bool}> $options
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function create(int $creatorId, array $input, array $options = []): array
    {
        $title = trim((string) ($input['title'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('Nội dung câu hỏi không được để trống.');
        }

        $type = (string) ($input['question_type'] ?? 'single_choice');

        if (in_array($type, self::CHOICE_TYPES, true)) {
            $this->validateChoiceOptions($type, $options);
        } elseif (in_array($type, ['fill_blank', 'calculation'], true) && trim((string) ($input['text_answer'] ?? '')) === '') {
            throw new RuntimeException('Câu hỏi dạng điền đáp án cần có đáp án đúng.');
        }

        $question = $this->questions->create($input, $creatorId);

        if (in_array($type, self::CHOICE_TYPES, true)) {
            $this->questions->replaceOptions((int) $question['id'], $options);
        }

        return $question;
    }

    public function findWithOptions(string $uuid): array
    {
        $question = $this->questions->findByUuid($uuid);

        if ($question === null) {
            throw new RuntimeException('Không tìm thấy câu hỏi.');
        }

        $question['options'] = $this->questions->findOptions((int) $question['id']);

        return $question;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $keyword, ?int $gradeId, ?string $type, ?string $difficulty): array
    {
        return $this->questions->search($keyword, $gradeId, $type, $difficulty);
    }

    public function publish(string $uuid): array
    {
        $question = $this->questions->findByUuid($uuid);

        if ($question === null) {
            throw new RuntimeException('Không tìm thấy câu hỏi.');
        }

        if (in_array($question['question_type'], self::CHOICE_TYPES, true)) {
            $options = $this->questions->findOptions((int) $question['id']);
            $this->validateChoiceOptions($question['question_type'], $options);
        }

        $this->questions->updateStatus((int) $question['id'], 'published');

        return $this->questions->findByUuid($uuid);
    }

    /**
     * @param array<int, array{content: string, is_correct: bool}> $options
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    private function validateChoiceOptions(string $type, array $options): void
    {
        if (count($options) < 2) {
            throw new RuntimeException('Câu hỏi trắc nghiệm cần ít nhất 2 lựa chọn.');
        }

        $correctCount = count(array_filter($options, fn ($o) => !empty($o['is_correct'])));

        if ($correctCount === 0) {
            throw new RuntimeException('Câu hỏi cần có ít nhất một đáp án đúng.');
        }

        if ($type === 'single_choice' && $correctCount > 1) {
            throw new RuntimeException('Câu hỏi một đáp án đúng không được có nhiều hơn 1 lựa chọn đúng.');
        }

        if ($type === 'true_false' && count($options) !== 2) {
            throw new RuntimeException('Câu hỏi Đúng/Sai phải có đúng 2 lựa chọn.');
        }
    }
}
