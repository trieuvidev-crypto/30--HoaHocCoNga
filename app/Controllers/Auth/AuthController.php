<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\Auth\AuthService;
use RuntimeException;

final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function register(Request $request, array $params): Response
    {
        $input = $request->allInput();

        $validator = new Validator($input, [
            'display_name' => 'required|max:150',
            'username' => 'required|alpha_dash|min:3|max:50',
            'email' => 'required|email|max:190',
            'password' => 'required|strong_password|confirmed',
        ], [
            'display_name' => 'Họ và tên',
            'username' => 'Tên đăng nhập',
            'email' => 'Email',
            'password' => 'Mật khẩu',
        ]);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        try {
            $user = $this->auth->register(
                (string) $input['display_name'],
                (string) $input['username'],
                (string) $input['email'],
                (string) $input['password']
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'REGISTRATION_FAILED', 422);
        }

        return Response::apiSuccess([
            'uuid' => $user['uuid'],
            'display_name' => $user['display_name'],
            'email' => $user['email'],
        ], 'Đăng ký thành công. Vui lòng kiểm tra email để xác minh tài khoản.', [], 201);
    }

    public function login(Request $request, array $params): Response
    {
        $input = $request->allInput();

        $validator = new Validator($input, [
            'identifier' => 'required',
            'password' => 'required',
        ], [
            'identifier' => 'Email hoặc tên đăng nhập',
            'password' => 'Mật khẩu',
        ]);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        try {
            $result = $this->auth->login(
                (string) $input['identifier'],
                (string) $input['password'],
                $request->ip(),
                (string) $request->input('via', 'api') === 'session'
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'LOGIN_FAILED', 401);
        }

        return Response::apiSuccess([
            'user' => [
                'uuid' => $result['user']['uuid'],
                'display_name' => $result['user']['display_name'],
                'email' => $result['user']['email'],
                'roles' => $result['roles'],
            ],
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_type' => 'Bearer',
        ], 'Đăng nhập thành công.');
    }

    public function refresh(Request $request, array $params): Response
    {
        $refreshToken = (string) $request->input('refresh_token', '');

        if ($refreshToken === '') {
            return Response::apiError('Thiếu refresh token.', [], 'VALIDATION_ERROR', 422);
        }

        try {
            $result = $this->auth->refresh($refreshToken);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'TOKEN_REFRESH_FAILED', 401);
        }

        return Response::apiSuccess($result, 'Làm mới token thành công.');
    }

    public function logout(Request $request, array $params): Response
    {
        $refreshToken = (string) $request->input('refresh_token', '');

        if ($refreshToken !== '') {
            $this->auth->logout($refreshToken);
        }

        return Response::apiSuccess(null, 'Đăng xuất thành công.');
    }

    public function forgotPassword(Request $request, array $params): Response
    {
        $email = (string) $request->input('email', '');

        $validator = new Validator(['email' => $email], ['email' => 'required|email'], ['email' => 'Email']);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        $this->auth->requestPasswordReset($email);

        // Always the same success message, regardless of whether the
        // account exists — prevents user enumeration.
        return Response::apiSuccess(null, 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.');
    }

    public function verifyEmail(Request $request, array $params): Response
    {
        $token = (string) $request->input('token', '');

        if ($token === '') {
            return Response::apiError('Thiếu mã xác minh.', [], 'VALIDATION_ERROR', 422);
        }

        try {
            $this->auth->verifyEmail($token);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'EMAIL_VERIFICATION_FAILED', 422);
        }

        return Response::apiSuccess(null, 'Xác minh email thành công. Bạn có thể đăng nhập ngay bây giờ.');
    }

    public function resendVerification(Request $request, array $params): Response
    {
        $email = (string) $request->input('email', '');

        $validator = new Validator(['email' => $email], ['email' => 'required|email'], ['email' => 'Email']);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        $this->auth->resendVerificationEmail($email);

        return Response::apiSuccess(null, 'Nếu tài khoản tồn tại và chưa xác minh, chúng tôi đã gửi lại email xác minh.');
    }

    public function resetPassword(Request $request, array $params): Response
    {
        $input = $request->allInput();

        $validator = new Validator($input, [
            'token' => 'required',
            'password' => 'required|strong_password|confirmed',
        ], [
            'token' => 'Mã đặt lại mật khẩu',
            'password' => 'Mật khẩu mới',
        ]);

        if ($validator->fails()) {
            return Response::apiError('Dữ liệu không hợp lệ.', $validator->errors(), 'VALIDATION_ERROR', 422);
        }

        try {
            $this->auth->resetPassword((string) $input['token'], (string) $input['password']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'PASSWORD_RESET_FAILED', 422);
        }

        return Response::apiSuccess(null, 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.');
    }
}
