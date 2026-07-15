-- ============================================================
-- Migration 0008: Notification core
-- Depends on: 0001 (users)
-- Per NOTIFICATION_SYSTEM.md
-- ============================================================

CREATE TABLE IF NOT EXISTS notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    key_name VARCHAR(100) NOT NULL COMMENT 'e.g. auth.password_reset, course.published',
    channel ENUM('in_app', 'email', 'sms', 'push') NOT NULL DEFAULT 'in_app',
    subject VARCHAR(200) NULL COMMENT 'used for email',
    body_template MEDIUMTEXT NOT NULL COMMENT 'supports {{variable}} placeholders',
    locale VARCHAR(10) NOT NULL DEFAULT 'vi',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notification_templates_uuid (uuid),
    UNIQUE KEY uq_notification_templates_key_channel_locale (key_name, channel, locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    category ENUM(
        'system', 'course', 'lesson', 'assignment', 'quiz', 'payment', 'order',
        'certificate', 'achievement', 'rank', 'teacher_announcement', 'forum',
        'comment', 'message', 'security', 'maintenance', 'promotion'
    ) NOT NULL DEFAULT 'system',
    priority ENUM('critical', 'high', 'normal', 'low', 'silent') NOT NULL DEFAULT 'normal',
    title VARCHAR(200) NOT NULL,
    body VARCHAR(500) NOT NULL,
    action_url VARCHAR(300) NULL,
    context JSON NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notifications_uuid (uuid),
    KEY idx_notifications_user_read (user_id, read_at),
    KEY idx_notifications_user_created (user_id, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(50) NOT NULL,
    in_app_enabled TINYINT(1) NOT NULL DEFAULT 1,
    email_enabled TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_notification_preferences_pair (user_id, category),
    CONSTRAINT fk_notification_preferences_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    channel ENUM('in_app', 'email', 'sms', 'push') NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    error_message VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notification_logs_user (user_id),
    KEY idx_notification_logs_status (status),
    KEY idx_notification_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
