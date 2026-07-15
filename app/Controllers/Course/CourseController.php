<?php

declare(strict_types=1);

namespace App\Controllers\Course;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\Course\CourseService;
use RuntimeException;

final class CourseController
{
    public function __construct(private readonly CourseService $courses)
    {
    }

    public function store(Request $request, array $params): Response
    {
        $input = $request->allInput();

        $validator = new Validator($input, [
            'title' => 'required|max:200',
            'short_description' => 'max:500',
            'price' => 'numeric',
        ], [
            'title' => 'Tên khóa học',
            'short_description' => 'Mô tả ngắn',
            'price' => 'Giá',
        ]);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        try {
            $course = $this->courses->create($this->currentUserId(), $input);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'COURSE_CREATE_FAILED', 422);
        }

        return Response::apiSuccess($course, 'Tạo khóa học thành công.', [], 201);
    }

    public function update(Request $request, array $params): Response
    {
        try {
            $course = $this->courses->update($this->currentUserId(), $params['uuid'], $request->allInput());
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'COURSE_UPDATE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess($course, 'Cập nhật khóa học thành công.');
    }

    public function publish(Request $request, array $params): Response
    {
        try {
            $course = $this->courses->publish($this->currentUserId(), $params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'COURSE_PUBLISH_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess($course, 'Xuất bản khóa học thành công.');
    }

    public function archive(Request $request, array $params): Response
    {
        try {
            $course = $this->courses->archive($this->currentUserId(), $params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'COURSE_ARCHIVE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess($course, 'Đã lưu trữ khóa học.');
    }

    public function duplicate(Request $request, array $params): Response
    {
        try {
            $course = $this->courses->duplicate($this->currentUserId(), $params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'COURSE_DUPLICATE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess($course, 'Nhân bản khóa học thành công.', [], 201);
    }

    public function destroy(Request $request, array $params): Response
    {
        try {
            $this->courses->delete($this->currentUserId(), $params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'COURSE_DELETE_FAILED', $this->statusFor($e));
        }

        return Response::apiSuccess(null, 'Đã xóa khóa học.');
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['auth_user_id'] ?? $_SERVER['AUTH_USER_ID'] ?? 0);
    }

    /**
     * Vietnamese ownership/permission messages map to 403; "not found"
     * messages map to 404; everything else is a 422 validation failure.
     * A small heuristic rather than a full typed-exception hierarchy —
     * acceptable here since messages are authored by this same module.
     */
    private function statusFor(RuntimeException $e): int
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Không tìm thấy')) {
            return 404;
        }

        if (str_contains($message, 'không có quyền')) {
            return 403;
        }

        return 422;
    }
}
