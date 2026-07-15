<?php

declare(strict_types=1);

namespace App\Controllers\Course;

use App\Core\Request;
use App\Core\Response;
use App\Services\Course\ChapterService;
use RuntimeException;

final class ChapterController
{
    public function __construct(private readonly ChapterService $chapters)
    {
    }

    public function index(Request $request, array $params): Response
    {
        try {
            $chapters = $this->chapters->listForCourse($params['courseUuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'NOT_FOUND', 404);
        }

        return Response::apiSuccess($chapters, 'Danh sách chương.');
    }

    public function store(Request $request, array $params): Response
    {
        $title = (string) $request->input('title', '');
        $description = $request->input('description');

        try {
            $chapter = $this->chapters->create($this->currentUserId(), $params['courseUuid'], $title, $description);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'CHAPTER_CREATE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess($chapter, 'Tạo chương thành công.', [], 201);
    }

    public function update(Request $request, array $params): Response
    {
        try {
            $chapter = $this->chapters->update(
                $this->currentUserId(),
                $params['courseUuid'],
                (int) $params['chapterId'],
                $request->allInput()
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'CHAPTER_UPDATE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess($chapter, 'Cập nhật chương thành công.');
    }

    public function reorder(Request $request, array $params): Response
    {
        try {
            $this->chapters->reorder(
                $this->currentUserId(),
                $params['courseUuid'],
                (int) $params['chapterId'],
                (int) $request->input('sort_order', 0)
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'CHAPTER_REORDER_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess(null, 'Đã cập nhật thứ tự chương.');
    }

    public function destroy(Request $request, array $params): Response
    {
        try {
            $this->chapters->delete($this->currentUserId(), $params['courseUuid'], (int) $params['chapterId']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'CHAPTER_DELETE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess(null, 'Đã xóa chương.');
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
