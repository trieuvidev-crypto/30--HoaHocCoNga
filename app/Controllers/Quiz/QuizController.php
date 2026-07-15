<?php

declare(strict_types=1);

namespace App\Controllers\Quiz;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\Quiz\QuizService;
use RuntimeException;

final class QuizController
{
    public function __construct(private readonly QuizService $quizzes)
    {
    }

    public function store(Request $request, array $params): Response
    {
        $input = $request->allInput();

        $validator = new Validator($input, [
            'title' => 'required|max:200',
        ], ['title' => 'Tên bài quiz']);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        try {
            $quiz = $this->quizzes->create($this->currentUserId(), $input);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUIZ_CREATE_FAILED', 422);
        }

        return Response::apiSuccess($quiz, 'Tạo bài quiz thành công.', [], 201);
    }

    public function addQuestion(Request $request, array $params): Response
    {
        $input = $request->allInput();

        try {
            $quiz = $this->quizzes->addQuestion(
                $params['uuid'],
                (string) ($input['question_uuid'] ?? ''),
                isset($input['points']) ? (float) $input['points'] : null
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUIZ_ADD_QUESTION_FAILED', 422);
        }

        return Response::apiSuccess($quiz, 'Đã thêm câu hỏi vào bài quiz.');
    }

    public function removeQuestion(Request $request, array $params): Response
    {
        try {
            $this->quizzes->removeQuestion($params['uuid'], $params['questionUuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUIZ_REMOVE_QUESTION_FAILED', 422);
        }

        return Response::apiSuccess(null, 'Đã xóa câu hỏi khỏi bài quiz.');
    }

    public function questions(Request $request, array $params): Response
    {
        try {
            $questions = $this->quizzes->questionsForQuiz($params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'NOT_FOUND', 404);
        }

        return Response::apiSuccess($questions, 'Danh sách câu hỏi trong bài quiz.');
    }

    public function publish(Request $request, array $params): Response
    {
        try {
            $quiz = $this->quizzes->publish($params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUIZ_PUBLISH_FAILED', 422);
        }

        return Response::apiSuccess($quiz, 'Xuất bản bài quiz thành công.');
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['auth_user_id'] ?? $_SERVER['AUTH_USER_ID'] ?? 0);
    }
}
