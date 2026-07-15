<?php

declare(strict_types=1);

/**
 * Usage: php database/seeders/0001_roles_permissions_seeder.php
 * Idempotent — safe to re-run (uses INSERT ... ON DUPLICATE KEY / IGNORE).
 */

require_once __DIR__ . '/../../bootstrap/helpers.php';
require_once __DIR__ . '/_bootstrap_seeder.php';

/** @var PDO $pdo */

$roles = [
    ['slug' => 'super_admin', 'name' => 'Quản trị viên tối cao', 'is_system' => 1],
    ['slug' => 'admin', 'name' => 'Quản trị viên', 'is_system' => 1],
    ['slug' => 'moderator', 'name' => 'Kiểm duyệt viên', 'is_system' => 1],
    ['slug' => 'teacher', 'name' => 'Giáo viên', 'is_system' => 1],
    ['slug' => 'teaching_assistant', 'name' => 'Trợ giảng', 'is_system' => 1],
    ['slug' => 'student', 'name' => 'Học sinh', 'is_system' => 1],
    ['slug' => 'support', 'name' => 'Hỗ trợ viên', 'is_system' => 1],
];

$insertRole = $pdo->prepare(
    'INSERT INTO roles (uuid, name, slug, is_system, created_at, updated_at)
     VALUES (:uuid, :name, :slug, :is_system, NOW(), NOW())
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);

foreach ($roles as $role) {
    $insertRole->execute([
        'uuid' => generate_uuid_v4(),
        'name' => $role['name'],
        'slug' => $role['slug'],
        'is_system' => $role['is_system'],
    ]);
}

echo 'Đã tạo ' . count($roles) . " vai trò hệ thống.\n";

$permissionGroups = [
    'users' => 'Người dùng',
    'courses' => 'Khóa học',
    'lessons' => 'Bài học',
    'documents' => 'Tài liệu',
    'payments' => 'Thanh toán',
    'forum' => 'Diễn đàn',
    'ai' => 'Trợ lý AI',
    'admin' => 'Quản trị hệ thống',
];

$groupIds = [];
$insertGroup = $pdo->prepare(
    'INSERT INTO permission_groups (uuid, name, slug, created_at, updated_at)
     VALUES (:uuid, :name, :slug, NOW(), NOW())
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);

foreach ($permissionGroups as $slug => $name) {
    $insertGroup->execute(['uuid' => generate_uuid_v4(), 'name' => $name, 'slug' => $slug]);
    $groupIds[$slug] = (int) $pdo->query(
        "SELECT id FROM permission_groups WHERE slug = " . $pdo->quote($slug)
    )->fetchColumn();
}

// action => Vietnamese verb, applied to every group above.
$actions = [
    'view' => 'Xem',
    'create' => 'Tạo',
    'edit' => 'Sửa',
    'delete' => 'Xóa',
    'publish' => 'Xuất bản',
    'manage' => 'Quản lý toàn bộ',
];

$insertPermission = $pdo->prepare(
    'INSERT INTO permissions (uuid, permission_group_id, name, slug, created_at, updated_at)
     VALUES (:uuid, :group_id, :name, :slug, NOW(), NOW())
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);

$permissionCount = 0;

foreach ($permissionGroups as $groupSlug => $groupName) {
    foreach ($actions as $actionSlug => $actionName) {
        $insertPermission->execute([
            'uuid' => generate_uuid_v4(),
            'group_id' => $groupIds[$groupSlug],
            'name' => "{$actionName} {$groupName}",
            'slug' => "{$groupSlug}.{$actionSlug}",
        ]);
        $permissionCount++;
    }
}

echo "Đã tạo {$permissionCount} quyền hạn.\n";

// super_admin and admin get every permission; student/teacher get a
// sensible working subset — full RBAC matrix per ROLE_PERMISSION_MATRIX.md
// is refined in Phase 2 (M2.2), this seed just makes the system usable.
$roleId = fn (string $slug) => (int) $pdo->query(
    'SELECT id FROM roles WHERE slug = ' . $pdo->quote($slug)
)->fetchColumn();

$allPermissionIds = $pdo->query('SELECT id FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
$attach = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');

foreach (['super_admin', 'admin'] as $adminSlug) {
    $id = $roleId($adminSlug);
    foreach ($allPermissionIds as $permissionId) {
        $attach->execute(['role_id' => $id, 'permission_id' => $permissionId]);
    }
}

$teacherPermissionSlugs = ['courses.create', 'courses.edit', 'courses.publish', 'lessons.create', 'lessons.edit', 'documents.create', 'documents.edit', 'forum.create'];
$teacherId = $roleId('teacher');

foreach ($teacherPermissionSlugs as $slug) {
    $permId = $pdo->query('SELECT id FROM permissions WHERE slug = ' . $pdo->quote($slug))->fetchColumn();
    if ($permId) {
        $attach->execute(['role_id' => $teacherId, 'permission_id' => $permId]);
    }
}

echo "Đã gán quyền cho các vai trò.\n";
