<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Single source of truth for sidebar navigation per role. Controllers
 * pass the resulting array to the dashboard layout via `navItems` —
 * never hardcode a nav array inline in a controller (that would
 * duplicate this structure and drift out of sync across pages).
 */
final class NavigationMenus
{
    /** @return array<int, array{href: string, icon: string, label: string, active: bool}> */
    public static function studentMenu(string $currentPath): array
    {
        return [
            ['href' => '/dashboard', 'icon' => 'course', 'label' => 'Tổng quan', 'active' => $currentPath === '/dashboard'],
            ['href' => '/dashboard/courses', 'icon' => 'course', 'label' => 'Khóa học của tôi', 'active' => $currentPath === '/dashboard/courses'],
            ['href' => '/chemistry-tools', 'icon' => 'flask', 'label' => 'Công cụ Hóa học', 'active' => $currentPath === '/chemistry-tools'],
        ];
    }

    /** @return array<int, array{href: string, icon: string, label: string, active: bool}> */
    public static function teacherMenu(string $currentPath): array
    {
        return [
            ['href' => '/teacher/dashboard', 'icon' => 'course', 'label' => 'Tổng quan', 'active' => $currentPath === '/teacher/dashboard'],
            ['href' => '/teacher/courses', 'icon' => 'course', 'label' => 'Khóa học của tôi', 'active' => str_starts_with($currentPath, '/teacher/courses')],
            ['href' => '/chemistry-tools', 'icon' => 'flask', 'label' => 'Công cụ Hóa học', 'active' => $currentPath === '/chemistry-tools'],
        ];
    }

    /** @return array<int, array{href: string, icon: string, label: string, active: bool}> */
    public static function adminMenu(string $currentPath): array
    {
        return [
            ['href' => '/administrator/dashboard', 'icon' => 'course', 'label' => 'Tổng quan', 'active' => $currentPath === '/administrator/dashboard'],
            ['href' => '/administrator/payments', 'icon' => 'flask', 'label' => 'Xác nhận thanh toán', 'active' => str_starts_with($currentPath, '/administrator/payments')],
        ];
    }
}
