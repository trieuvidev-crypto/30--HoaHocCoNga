<?php

declare(strict_types=1);

namespace App\Controllers\Course;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\Course\LessonService;
use RuntimeException;

final class LessonController
{
    public function __construct(private readonly LessonService $lessons)
    {
    }

    public function index(Request $request, array $params): Response
    {
        return Response::apiSuccess(
            $this->lessons->listForChapter((int) $params['chapterId']),
            'Danh sách bài học.'
        );
    }

    public function store(Request $request, array $params): Response
    {
        $input = $request->allInput();

        $validator = new Validator($input, [
            'title' => 'required|max:200',
        ], ['title' => 'Tên bài học']);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        try {
            $lesson = $this->lessons->create(
                $this->currentUserId(),
                $params['courseUuid'],
                (int) $params['chapterId'],
                $input
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'LESSON_CREATE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess($lesson, 'Tạo bài học thành công.', [], 201);
    }

    public function update(Request $request, array $params): Response
    {
        try {
            $lesson = $this->lessons->update(
                $this->currentUserId(),
                $params['courseUuid'],
                (int) $params['lessonId'],
                $request->allInput()
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'LESSON_UPDATE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess($lesson, 'Cập nhật bài học thành công.');
    }

    public function reorder(Request $request, array $params): Response
    {
        try {
            $this->lessons->reorder(
                $this->currentUserId(),
                $params['courseUuid'],
                (int) $params['lessonId'],
                (int) $request->input('sort_order', 0)
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'LESSON_REORDER_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess(null, 'Đã cập nhật thứ tự bài học.');
    }

    public function destroy(Request $request, array $params): Response
    {
        try {
            $this->lessons->delete($this->currentUserId(), $params['courseUuid'], (int) $params['lessonId']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'LESSON_DELETE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess(null, 'Đã xóa bài học.');
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['auth_user_id'] ?? $_SERVER['AUTH_USER_ID'] ?? 0);
    }

    private function statusFor(RuntimeException $e): int
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Không tìm thấy') || str_contains($message, 'không thuộc khóa học')) {
            return 404;
        }

        if (str_contains($message, 'không có quyền')) {
            return 403;
        }

        return 422;
    }
}
