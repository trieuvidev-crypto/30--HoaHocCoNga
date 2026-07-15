<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use App\Core\Cache;
use App\Core\Database;

/**
 * Runtime permission/role resolution. Permissions are cached per-user
 * for a short TTL (invalidated explicitly on role/permission change —
 * see invalidateUserCache()) since this is checked on nearly every
 * authenticated request and must not add a query-per-permission cost.
 */
final class PermissionService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(private readonly Database $db, private readonly Cache $cache)
    {
    }

    public function hasPermission(int $userId, string $permissionSlug): bool
    {
        return in_array($permissionSlug, $this->getUserPermissionSlugs($userId), true);
    }

    public function hasAnyPermission(int $userId, array $permissionSlugs): bool
    {
        $userPermissions = $this->getUserPermissionSlugs($userId);

        return count(array_intersect($permissionSlugs, $userPermissions)) > 0;
    }

    public function hasRole(int $userId, string $roleSlug): bool
    {
        return in_array($roleSlug, $this->getUserRoleSlugs($userId), true);
    }

    /** @return array<int, string> */
    public function getUserRoleSlugs(int $userId): array
    {
        return $this->cache->remember("user:{$userId}:roles", self::CACHE_TTL_SECONDS, function () use ($userId) {
            $rows = $this->db->fetchAll(
                'SELECT r.slug FROM roles r
                 INNER JOIN user_roles ur ON ur.role_id = r.id
                 WHERE ur.user_id = :user_id',
                ['user_id' => $userId]
            );

            return array_column($rows, 'slug');
        });
    }

    /** @return array<int, string> */
    public function getUserPermissionSlugs(int $userId): array
    {
        return $this->cache->remember("user:{$userId}:permissions", self::CACHE_TTL_SECONDS, function () use ($userId) {
            // Includes role_hierarchy: a user holding a parent role inherits
            // every permission granted to its child roles.
            $rows = $this->db->fetchAll(
                'SELECT DISTINCT p.slug
                 FROM permissions p
                 INNER JOIN role_permissions rp ON rp.permission_id = p.id
                 INNER JOIN roles r ON r.id = rp.role_id
                 WHERE r.id IN (
                     SELECT ur.role_id FROM user_roles ur WHERE ur.user_id = :user_id_1
                     UNION
                     SELECT rh.child_role_id FROM role_hierarchy rh
                     INNER JOIN user_roles ur2 ON ur2.role_id = rh.parent_role_id
                     WHERE ur2.user_id = :user_id_2
                 )',
                ['user_id_1' => $userId, 'user_id_2' => $userId]
            );

            return array_column($rows, 'slug');
        });
    }

    public function invalidateUserCache(int $userId): void
    {
        $this->cache->forget("user:{$userId}:roles");
        $this->cache->forget("user:{$userId}:permissions");
    }
}
