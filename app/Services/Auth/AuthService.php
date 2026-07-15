<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database;
use App\Core\Events\EventDispatcher;
use App\Events\UserRegisteredEvent;
use App\Repositories\UserRepository;
use App\Services\Notification\NotificationService;
use RuntimeException;

/**
 * Business logic for authentication. Controllers call this; this never
 * touches PDO directly (that's UserRepository's job) and never renders
 * a response (that's the Controller's job).
 */
final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenService $tokens,
        private readonly Database $db,
        private readonly EventDispatcher $events,
        private readonly NotificationService $notifications
    ) {
    }

    /**
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function register(string $displayName, string $username, string $email, string $password): array
    {
        if ($this->users->emailExists($email)) {
            throw new RuntimeException('Email này đã được đăng ký.');
        }

        if ($this->users->usernameExists($username)) {
            throw new RuntimeException('Tên đăng nhập này đã tồn tại.');
        }

        $hash = password_hash($password, config('security.password.algo'), config('security.password.options'));

        $user = $this->db->transaction(function () use ($displayName, $username, $email, $hash) {
            $user = $this->users->create([
                'display_name' => $displayName,
                'username' => $username,
                'email' => $email,
                'password_hash' => $hash,
            ]);

            $this->users->assignDefaultRole((int) $user['id'], 'student');

            return $user;
        });

        // Dispatched only after the transaction commits — listeners must
        // never see a half-committed registration.
        $this->events->dispatch(new UserRegisteredEvent((int) $user['id'], $user['uuid'], $user['email']));

        $this->sendVerificationEmail((int) $user['id'], $user['email'], $user['display_name']);

        return $user;
    }

    private function sendVerificationEmail(int $userId, string $email, string $displayName): void
    {
        $token = bin2hex(random_bytes(32));

        $this->db->query(
            'INSERT INTO email_verifications (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :hash, :expires_at, NOW())',
            [
                'user_id' => $userId,
                'hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 86400), // 24h
            ]
        );

        $verifyUrl = rtrim((string) config('app.url'), '/') . '/verify-email?token=' . $token;

        $this->notifications->sendTransactionalEmail(
            $email,
            'Xác minh tài khoản HoaHocCoNga.Com',
            "Chào {$displayName}, vui lòng xác minh email để kích hoạt tài khoản. Liên kết có hiệu lực trong 24 giờ."
                . '<br><br><a href="' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '" '
                . 'style="display:inline-block;padding:10px 20px;background:#2563EB;color:#fff;border-radius:8px;text-decoration:none;">'
                . 'Xác minh tài khoản</a>'
        );
    }

    /**
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function verifyEmail(string $token): void
    {
        $hash = hash('sha256', $token);

        $record = $this->db->fetchOne(
            'SELECT * FROM email_verifications WHERE token_hash = :hash AND verified_at IS NULL AND expires_at > NOW()',
            ['hash' => $hash]
        );

        if ($record === null) {
            throw new RuntimeException('Liên kết xác minh không hợp lệ hoặc đã hết hạn.');
        }

        $this->db->transaction(function () use ($record) {
            $this->users->markEmailVerified((int) $record['user_id']);

            $this->db->query(
                'UPDATE email_verifications SET verified_at = NOW() WHERE id = :id',
                ['id' => $record['id']]
            );
        });
    }

    /**
     * Re-sends the verification email for an account stuck in 'pending'
     * status — the only recovery path if the original email was lost or
     * the 24h token expired, per ERROR_HANDLING.md's requirement that
     * every failure path has a recovery route, not just resetPassword.
     */
    public function resendVerificationEmail(string $email): void
    {
        $user = $this->users->findByEmail($email);

        // Same anti-enumeration behavior as requestPasswordReset: succeed
        // silently regardless of whether the account exists or is
        // already verified.
        if ($user === null || $user['status'] !== 'pending') {
            return;
        }

        $this->sendVerificationEmail((int) $user['id'], $user['email'], $user['display_name']);

    }

    /**
     * @return array{user: array, access_token: string, refresh_token: string}
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function login(string $identifier, string $password, string $ip, bool $useSession = false): array
    {
        $user = str_contains($identifier, '@')
            ? $this->users->findByEmail($identifier)
            : $this->users->findByUsername($identifier);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            // Deliberately identical message for "no such user" and "wrong
            // password" to avoid user enumeration (see ERROR_HANDLING.md).
            throw new RuntimeException('Thông tin đăng nhập không chính xác.');
        }

        if ($user['status'] === 'suspended' || $user['status'] === 'banned') {
            throw new RuntimeException('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.');
        }

        if ($user['status'] === 'pending') {
            throw new RuntimeException('Vui lòng xác minh email trước khi đăng nhập.');
        }

        $this->users->recordLogin((int) $user['id'], $ip);

        if ($useSession) {
            // Regenerate the session ID on privilege change (login) to
            // prevent session fixation, per SECURITY_CHECKLIST.md.
            session_regenerate_id(true);
            $_SESSION['auth_user_id'] = (int) $user['id'];
            $_SESSION['auth_user_uuid'] = $user['uuid'];
        }

        // Roles are embedded in the access token so the Node.js realtime
        // server can assign socket rooms (admin/teacher) directly from the
        // JWT payload without a database round-trip on every connection.
        $roles = $this->users->getRoleSlugs((int) $user['id']);

        $accessToken = $this->tokens->issueAccessToken((int) $user['id'], $user['uuid'], ['roles' => $roles]);
        $refreshToken = $this->tokens->issueRefreshToken((int) $user['id'], $user['uuid']);

        $this->db->query(
            'INSERT INTO user_sessions (uuid, user_id, refresh_token_hash, ip_address, expires_at, created_at)
             VALUES (:uuid, :user_id, :hash, :ip, :expires_at, NOW())',
            [
                'uuid' => generate_uuid_v4(),
                'user_id' => $user['id'],
                'hash' => hash('sha256', $refreshToken),
                'ip' => $ip,
                'expires_at' => date('Y-m-d H:i:s', time() + (int) config('security.jwt.refresh_ttl_days', 14) * 86400),
            ]
        );

        return ['user' => $user, 'access_token' => $accessToken, 'refresh_token' => $refreshToken, 'roles' => $roles];
    }

    public function refresh(string $refreshToken): array
    {
        $payload = $this->tokens->validateRefreshToken($refreshToken);

        if ($payload === null) {
            throw new RuntimeException('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
        }

        $hash = hash('sha256', $refreshToken);
        $session = $this->db->fetchOne(
            'SELECT * FROM user_sessions WHERE refresh_token_hash = :hash AND revoked_at IS NULL AND expires_at > NOW()',
            ['hash' => $hash]
        );

        if ($session === null) {
            throw new RuntimeException('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
        }

        $user = $this->users->findById((int) $payload['sub']);

        if ($user === null) {
            throw new RuntimeException('Không tìm thấy tài khoản.');
        }

        $roles = $this->users->getRoleSlugs((int) $user['id']);

        return [
            'access_token' => $this->tokens->issueAccessToken((int) $user['id'], $user['uuid'], ['roles' => $roles]),
        ];
    }

    public function logout(string $refreshToken): void
    {
        $this->db->query(
            'UPDATE user_sessions SET revoked_at = NOW() WHERE refresh_token_hash = :hash',
            ['hash' => hash('sha256', $refreshToken)]
        );

        if (!empty($_SESSION['auth_user_id'])) {
            unset($_SESSION['auth_user_id'], $_SESSION['auth_user_uuid']);
            session_regenerate_id(true);
        }
    }

    public function requestPasswordReset(string $email): void
    {
        $user = $this->users->findByEmail($email);

        // Always behave as if the email was sent, regardless of whether
        // the account exists — prevents user enumeration via this endpoint.
        if ($user === null) {
            return;
        }

        $token = bin2hex(random_bytes(32));

        $this->db->query(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :hash, :expires_at, NOW())',
            [
                'user_id' => $user['id'],
                'hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]
        );

        $resetUrl = rtrim((string) config('app.url'), '/') . '/reset-password?token=' . $token;

        $this->notifications->sendTransactionalEmail(
            $user['email'],
            'Đặt lại mật khẩu HoaHocCoNga.Com',
            'Bạn (hoặc ai đó) vừa yêu cầu đặt lại mật khẩu cho tài khoản này. '
                . 'Nhấn nút bên dưới để đặt mật khẩu mới. Liên kết có hiệu lực trong 1 giờ. '
                . 'Nếu bạn không yêu cầu, vui lòng bỏ qua email này.'
                . '<br><br><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '" '
                . 'style="display:inline-block;padding:10px 20px;background:#2563EB;color:#fff;border-radius:8px;text-decoration:none;">'
                . 'Đặt lại mật khẩu</a>'
        );
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        $hash = hash('sha256', $token);

        $reset = $this->db->fetchOne(
            'SELECT * FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW()',
            ['hash' => $hash]
        );

        if ($reset === null) {
            throw new RuntimeException('Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
        }

        $newHash = password_hash($newPassword, config('security.password.algo'), config('security.password.options'));

        $this->db->transaction(function () use ($reset, $newHash) {
            $this->users->updatePassword((int) $reset['user_id'], $newHash);

            $this->db->query(
                'UPDATE password_resets SET used_at = NOW() WHERE id = :id',
                ['id' => $reset['id']]
            );

            $this->db->query(
                'UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = :user_id AND revoked_at IS NULL',
                ['user_id' => $reset['user_id']]
            );
        });
    }
}
