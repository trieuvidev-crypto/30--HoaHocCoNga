-- ============================================================
-- Migration 0004: Media / File storage
-- Depends on: 0001 (users)
-- ============================================================

CREATE TABLE IF NOT EXISTS media_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    disk VARCHAR(30) NOT NULL DEFAULT 'local',
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    duration_seconds INT UNSIGNED NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    visibility ENUM('public', 'authenticated', 'purchased', 'private') NOT NULL DEFAULT 'public',
    uploader_id BIGINT UNSIGNED NULL,
    owner_type VARCHAR(50) NULL COMMENT 'polymorphic: course, lesson, document, user, ...',
    owner_id BIGINT UNSIGNED NULL,
    download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_media_files_uuid (uuid),
    KEY idx_media_files_checksum (checksum_sha256),
    KEY idx_media_files_owner (owner_type, owner_id),
    KEY idx_media_files_uploader (uploader_id),
    CONSTRAINT fk_media_files_uploader FOREIGN KEY (uploader_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD CONSTRAINT fk_users_avatar_media FOREIGN KEY (avatar_media_id) REFERENCES media_files (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_users_cover_media FOREIGN KEY (cover_media_id) REFERENCES media_files (id) ON DELETE SET NULL;
