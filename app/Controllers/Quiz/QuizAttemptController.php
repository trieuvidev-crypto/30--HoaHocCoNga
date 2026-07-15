<?php

declare(strict_types=1);

namespace App\Controllers\Quiz;

use App\Core\Request;
use App\Core\Response;
use App\Services\Quiz\QuizAttemptService;
use RuntimeException;

final class QuizAttemptController
{
    public function __construct(private readonly QuizAttemptService $attempts)
    {
    }

    public function start(Request $request, array $params): Response
    {
        try {
            $attempt = $this->attempts->start($this->currentUserId(), $params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUIZ_START_FAILED', 422);
        }

        return Response::apiSuccess($attempt, 'Bắt đầu làm bài.');
    }

    public function answer(Request $request, array $params): Response
    {
        $input = $request->allInput();
        $selectedOptionIds = isset($input['selected_option_ids']) ? array_map('intval', (array) $input['selected_option_ids']) : null;

        try {
            $this->attempts->submitAnswer(
                $this->currentUserId(),
                $params['attemptUuid'],
                (string) ($input['question_uuid'] ?? ''),
                $selectedOptionIds,
                isset($input['text_answer']) ? (string) $input['text_answer'] : null
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUIZ_ANSWER_FAILED', 422);
        }

        return Response::apiSuccess(null, 'Đã ghi nhận câu trả lời.');
    }

    public function finish(Request $request, array $params): Response
    {
        try {
            $result = $this->attempts->finish($this->currentUserId(), $params['attemptUuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUIZ_FINISH_FAILED', 422);
        }

        return Response::apiSuccess($result, 'Nộp bài thành công.');
    }

    public function history(Request $request, array $params): Response
    {
        try {
            $history = $this->attempts->history($this->currentUserId(), $params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'NOT_FOUND', 404);
        }

        return Response::apiSuccess($history, 'Lịch sử làm bài.');
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['auth_user_id'] ?? $_SERVER['AUTH_USER_ID'] ?? 0);
    }
}
