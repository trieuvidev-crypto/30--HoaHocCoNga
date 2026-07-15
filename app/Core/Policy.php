<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base class for resource-ownership policies (e.g. CoursePolicy,
 * LessonPolicy). Permission slugs answer "can this role do X at all";
 * Policies answer the second question every module needs on top of
 * that: "does this specific user own/control this specific resource".
 *
 * Concrete policies extend this and implement resource-specific checks;
 * this base only provides the shared admin-bypass rule (an admin/super
 * admin permission always satisfies ownership too).
 */
abstract class Policy
{
    public function __construct(protected readonly \App\Services\Rbac\PermissionService $permissions)
    {
    }

    protected function isAdmin(int $userId): bool
    {
        return $this->permissions->hasRole($userId, 'admin')
            || $this->permissions->hasRole($userId, 'super_admin');
    }
}
