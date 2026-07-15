<?php

declare(strict_types=1);

/**
 * Usage: php database/seeders/0002_system_reference_seeder.php
 */

require_once __DIR__ . '/../../bootstrap/helpers.php';
require_once __DIR__ . '/_bootstrap_seeder.php';

/** @var PDO $pdo */

// Vietnam as the sole seeded country for now (multi-country not needed yet).
$pdo->prepare(
    'INSERT INTO countries (name, iso_code, phone_code) VALUES (:name, :iso, :phone)
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
)->execute(['name' => 'Việt Nam', 'iso' => 'VN', 'phone' => '+84']);

$countryId = (int) $pdo->query("SELECT id FROM countries WHERE iso_code = 'VN'")->fetchColumn();

// A representative set of provinces/cities (full 63-unit list is a
// dedicated data-import task, not hand-typed here to avoid transcription
// errors in geographic/administrative reference data).
$provinces = [
    ['name' => 'Hà Nội', 'region' => 'Bắc Bộ'],
    ['name' => 'Thành phố Hồ Chí Minh', 'region' => 'Nam Bộ'],
    ['name' => 'Đà Nẵng', 'region' => 'Trung Bộ'],
    ['name' => 'Hải Phòng', 'region' => 'Bắc Bộ'],
    ['name' => 'Cần Thơ', 'region' => 'Nam Bộ'],
    ['name' => 'Nghệ An', 'region' => 'Trung Bộ'],
    ['name' => 'Thanh Hóa', 'region' => 'Trung Bộ'],
    ['name' => 'Bắc Ninh', 'region' => 'Bắc Bộ'],
    ['name' => 'Đồng Nai', 'region' => 'Nam Bộ'],
    ['name' => 'Khánh Hòa', 'region' => 'Trung Bộ'],
];

$insertProvince = $pdo->prepare(
    'INSERT INTO provinces (country_id, name, region) VALUES (:country_id, :name, :region)'
);

foreach ($provinces as $province) {
    $exists = $pdo->prepare('SELECT id FROM provinces WHERE name = :name');
    $exists->execute(['name' => $province['name']]);

    if (!$exists->fetchColumn()) {
        $insertProvince->execute([
            'country_id' => $countryId,
            'name' => $province['name'],
            'region' => $province['region'],
        ]);
    }
}

echo 'Đã tạo ' . count($provinces) . " tỉnh/thành phố.\n";

// Grades — matches CHEMISTRY_DOMAIN.md §Subject Structure exactly.
$grades = [
    ['name' => 'Lớp 8', 'slug' => 'lop-8', 'level' => 'thcs', 'sort_order' => 1],
    ['name' => 'Lớp 9', 'slug' => 'lop-9', 'level' => 'thcs', 'sort_order' => 2],
    ['name' => 'Lớp 10', 'slug' => 'lop-10', 'level' => 'thpt', 'sort_order' => 3],
    ['name' => 'Lớp 11', 'slug' => 'lop-11', 'level' => 'thpt', 'sort_order' => 4],
    ['name' => 'Lớp 12', 'slug' => 'lop-12', 'level' => 'thpt', 'sort_order' => 5],
    ['name' => 'Đại học', 'slug' => 'dai-hoc', 'level' => 'university', 'sort_order' => 6],
    ['name' => 'Học sinh giỏi Hóa học', 'slug' => 'hoc-sinh-gioi', 'level' => 'olympic', 'sort_order' => 7],
    ['name' => 'Olympic Hóa học', 'slug' => 'olympic-hoa-hoc', 'level' => 'olympic', 'sort_order' => 8],
];

$insertGrade = $pdo->prepare(
    'INSERT INTO grades (uuid, name, slug, level, sort_order) VALUES (:uuid, :name, :slug, :level, :sort_order)
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);

foreach ($grades as $grade) {
    $insertGrade->execute([
        'uuid' => generate_uuid_v4(),
        'name' => $grade['name'],
        'slug' => $grade['slug'],
        'level' => $grade['level'],
        'sort_order' => $grade['sort_order'],
    ]);
}

echo 'Đã tạo ' . count($grades) . " khối lớp.\n";

// Subjects — Hóa học is primary; a few siblings seeded for cross-subject
// features (search scoping, teacher profiles) to behave correctly.
$subjects = [
    ['name' => 'Hóa học', 'slug' => 'hoa-hoc'],
    ['name' => 'Vật lý', 'slug' => 'vat-ly'],
    ['name' => 'Sinh học', 'slug' => 'sinh-hoc'],
    ['name' => 'Toán học', 'slug' => 'toan-hoc'],
];

$insertSubject = $pdo->prepare(
    'INSERT INTO subjects (uuid, name, slug) VALUES (:uuid, :name, :slug)
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);

foreach ($subjects as $subject) {
    $insertSubject->execute(['uuid' => generate_uuid_v4(), 'name' => $subject['name'], 'slug' => $subject['slug']]);
}

echo 'Đã tạo ' . count($subjects) . " môn học.\n";

// Course categories aligned to CHEMISTRY_DOMAIN.md subject structure.
$categories = [
    ['name' => 'Hóa học Đại cương', 'slug' => 'hoa-hoc-dai-cuong'],
    ['name' => 'Hóa học Hữu cơ', 'slug' => 'hoa-hoc-huu-co'],
    ['name' => 'Hóa học Vô cơ', 'slug' => 'hoa-hoc-vo-co'],
    ['name' => 'Hóa lý', 'slug' => 'hoa-ly'],
    ['name' => 'Hóa phân tích', 'slug' => 'hoa-phan-tich'],
    ['name' => 'Hóa Sinh', 'slug' => 'hoa-sinh'],
    ['name' => 'Ôn thi THPT Quốc gia', 'slug' => 'on-thi-thpt-quoc-gia'],
    ['name' => 'Ôn thi Học sinh giỏi', 'slug' => 'on-thi-hoc-sinh-gioi'],
];

$insertCategory = $pdo->prepare(
    'INSERT INTO course_categories (uuid, name, slug, sort_order, created_at, updated_at)
     VALUES (:uuid, :name, :slug, :sort_order, NOW(), NOW())
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);

foreach ($categories as $index => $category) {
    $insertCategory->execute([
        'uuid' => generate_uuid_v4(),
        'name' => $category['name'],
        'slug' => $category['slug'],
        'sort_order' => $index,
    ]);
}

echo 'Đã tạo ' . count($categories) . " danh mục khóa học.\n";
