<?php

declare(strict_types=1);

namespace App\Controllers\Quiz;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\Quiz\QuestionService;
use RuntimeException;

final class QuestionController
{
    public function __construct(private readonly QuestionService $questions)
    {
    }

    public function store(Request $request, array $params): Response
    {
        $input = $request->allInput();

        $validator = new Validator($input, [
            'title' => 'required|max:500',
            'question_type' => 'required',
        ], [
            'title' => 'Nội dung câu hỏi',
            'question_type' => 'Loại câu hỏi',
        ]);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        try {
            $question = $this->questions->create($this->currentUserId(), $input, $input['options'] ?? []);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUESTION_CREATE_FAILED', 422);
        }

        return Response::apiSuccess($question, 'Tạo câu hỏi thành công.', [], 201);
    }

    public function show(Request $request, array $params): Response
    {
        try {
            $question = $this->questions->findWithOptions($params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'NOT_FOUND', 404);
        }

        return Response::apiSuccess($question, 'Chi tiết câu hỏi.');
    }

    public function index(Request $request, array $params): Response
    {
        $results = $this->questions->search(
            $request->query('keyword'),
            $request->query('grade') !== null ? (int) $request->query('grade') : null,
            $request->query('type'),
            $request->query('difficulty')
        );

        return Response::apiSuccess($results, 'Danh sách câu hỏi.');
    }

    public function publish(Request $request, array $params): Response
    {
        try {
            $question = $this->questions->publish($params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'QUESTION_PUBLISH_FAILED', 422);
        }

        return Response::apiSuccess($question, 'Xuất bản câu hỏi thành công.');
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['auth_user_id'] ?? $_SERVER['AUTH_USER_ID'] ?? 0);
    }
}
