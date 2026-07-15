<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Data access only — no business rules. AuthService orchestrates the
 * actual registration/login workflow and calls into this repository.
 */
final class UserRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL',
            ['id' => $id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE email = :email AND deleted_at IS NULL',
            ['email' => mb_strtolower($email)]
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE username = :username AND deleted_at IS NULL',
            ['username' => mb_strtolower($username)]
        );
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function usernameExists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function create(array $attributes): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO users (uuid, display_name, username, email, password_hash, status, created_at, updated_at)
             VALUES (:uuid, :display_name, :username, :email, :password_hash, :status, NOW(), NOW())',
            [
                'uuid' => $uuid,
                'display_name' => $attributes['display_name'],
                'username' => mb_strtolower($attributes['username']),
                'email' => mb_strtolower($attributes['email']),
                'password_hash' => $attributes['password_hash'],
                'status' => $attributes['status'] ?? 'pending',
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->db->query(
            'UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id',
            ['hash' => $passwordHash, 'id' => $userId]
        );
    }

    public function markEmailVerified(int $userId): void
    {
        $this->db->query(
            "UPDATE users SET email_verified_at = NOW(), status = 'active', updated_at = NOW() WHERE id = :id",
            ['id' => $userId]
        );
    }

    public function recordLogin(int $userId, string $ip): void
    {
        $this->db->query(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id',
            ['ip' => $ip, 'id' => $userId]
        );
    }

    public function assignDefaultRole(int $userId, string $roleSlug = 'student'): void
    {
        $role = $this->db->fetchOne('SELECT id FROM roles WHERE slug = :slug', ['slug' => $roleSlug]);

        if ($role === null) {
            return;
        }

        $this->db->query(
            'INSERT IGNORE INTO user_roles (user_id, role_id, assigned_at) VALUES (:user_id, :role_id, NOW())',
            ['user_id' => $userId, 'role_id' => $role['id']]
        );
    }

    /** @return array<int, string> */
    public function getRoleSlugs(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT r.slug FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :user_id',
            ['user_id' => $userId]
        );

        return array_column($rows, 'slug');
    }
}
