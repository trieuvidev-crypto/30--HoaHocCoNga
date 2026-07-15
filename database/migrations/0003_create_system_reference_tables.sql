-- ============================================================
-- Migration 0003: System reference data
-- Depends on: none (referenced by 0001 via nullable FKs added here)
-- ============================================================

CREATE TABLE IF NOT EXISTS countries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    iso_code CHAR(2) NOT NULL,
    phone_code VARCHAR(10) NULL,
    UNIQUE KEY uq_countries_iso (iso_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provinces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NULL,
    region VARCHAR(50) NULL COMMENT 'Bắc Bộ / Trung Bộ / Nam Bộ',
    KEY idx_provinces_country (country_id),
    CONSTRAINT fk_provinces_country FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS districts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    province_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    KEY idx_districts_province (province_id),
    CONSTRAINT fk_districts_province FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schools (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    province_id BIGINT UNSIGNED NULL,
    name VARCHAR(200) NOT NULL,
    type ENUM('thcs', 'thpt', 'university', 'center', 'other') NOT NULL DEFAULT 'other',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_schools_uuid (uuid),
    KEY idx_schools_province (province_id),
    FULLTEXT KEY ft_schools_name (name),
    CONSTRAINT fk_schools_province FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT 'Lớp 8, Lớp 9, ..., Đại học, Olympic',
    slug VARCHAR(100) NOT NULL,
    level ENUM('thcs', 'thpt', 'university', 'olympic', 'general') NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_grades_uuid (uuid),
    UNIQUE KEY uq_grades_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_subjects_uuid (uuid),
    UNIQUE KEY uq_subjects_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academic_years (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL COMMENT 'e.g. 2026-2027',
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_academic_years_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS semesters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL COMMENT 'Học kỳ 1 / Học kỳ 2',
    sort_order INT NOT NULL DEFAULT 0,
    KEY idx_semesters_year (academic_year_id),
    CONSTRAINT fk_semesters_year FOREIGN KEY (academic_year_id) REFERENCES academic_years (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now that grades/provinces/schools exist, attach the FKs referenced by users (0001).
ALTER TABLE users
    ADD CONSTRAINT fk_users_school FOREIGN KEY (school_id) REFERENCES schools (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_users_grade FOREIGN KEY (grade_id) REFERENCES grades (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_users_province FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE SET NULL;
