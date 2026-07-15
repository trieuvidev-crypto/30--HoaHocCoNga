-- ============================================================
-- Migration 0005: Course domain (Course, Chapter, Lesson, Enrollment)
-- Depends on: 0001 (users), 0003 (grades/subjects), 0004 (media)
-- ============================================================

CREATE TABLE IF NOT EXISTS course_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    description VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_course_categories_uuid (uuid),
    UNIQUE KEY uq_course_categories_slug (slug),
    KEY idx_course_categories_parent (parent_id),
    CONSTRAINT fk_course_categories_parent FOREIGN KEY (parent_id) REFERENCES course_categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    UNIQUE KEY uq_course_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    short_description VARCHAR(500) NULL,
    description MEDIUMTEXT NULL,
    course_type ENUM('free', 'paid', 'combo', 'live', 'recorded', 'private', 'membership') NOT NULL DEFAULT 'paid',
    category_id BIGINT UNSIGNED NULL,
    grade_id BIGINT UNSIGNED NULL,
    subject_id BIGINT UNSIGNED NULL,
    primary_teacher_id BIGINT UNSIGNED NOT NULL,
    cover_media_id BIGINT UNSIGNED NULL,
    banner_media_id BIGINT UNSIGNED NULL,
    trailer_media_id BIGINT UNSIGNED NULL,
    difficulty ENUM('beginner', 'intermediate', 'advanced', 'expert') NOT NULL DEFAULT 'beginner',
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    sale_price DECIMAL(12,2) NULL,
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    estimated_duration_minutes INT UNSIGNED NULL,
    language VARCHAR(10) NOT NULL DEFAULT 'vi',
    has_certificate TINYINT(1) NOT NULL DEFAULT 1,
    visibility ENUM('public', 'unlisted', 'private') NOT NULL DEFAULT 'public',
    status ENUM('draft', 'in_review', 'published', 'scheduled', 'archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    scheduled_at DATETIME NULL,
    seo_title VARCHAR(200) NULL,
    seo_description VARCHAR(300) NULL,
    rating_average DECIMAL(3,2) NOT NULL DEFAULT 0,
    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    enrollment_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_courses_uuid (uuid),
    UNIQUE KEY uq_courses_slug (slug),
    KEY idx_courses_category_status (category_id, status),
    KEY idx_courses_grade (grade_id),
    KEY idx_courses_teacher (primary_teacher_id),
    KEY idx_courses_status_published (status, published_at),
    KEY idx_courses_price (price),
    KEY idx_courses_rating (rating_average),
    FULLTEXT KEY ft_courses_title_desc (title, short_description),
    CONSTRAINT fk_courses_category FOREIGN KEY (category_id) REFERENCES course_categories (id) ON DELETE SET NULL,
    CONSTRAINT fk_courses_grade FOREIGN KEY (grade_id) REFERENCES grades (id) ON DELETE SET NULL,
    CONSTRAINT fk_courses_subject FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL,
    CONSTRAINT fk_courses_teacher FOREIGN KEY (primary_teacher_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_courses_cover_media FOREIGN KEY (cover_media_id) REFERENCES media_files (id) ON DELETE SET NULL,
    CONSTRAINT fk_courses_banner_media FOREIGN KEY (banner_media_id) REFERENCES media_files (id) ON DELETE SET NULL,
    CONSTRAINT fk_courses_trailer_media FOREIGN KEY (trailer_media_id) REFERENCES media_files (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_tag_relations (
    course_id BIGINT UNSIGNED NOT NULL,
    course_tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (course_id, course_tag_id),
    CONSTRAINT fk_course_tag_relations_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_tag_relations_tag FOREIGN KEY (course_tag_id) REFERENCES course_tags (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_assistants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    role ENUM('co_teacher', 'assistant') NOT NULL DEFAULT 'assistant',
    UNIQUE KEY uq_course_assistants_pair (course_id, teacher_id),
    CONSTRAINT fk_course_assistants_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_assistants_teacher FOREIGN KEY (teacher_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_requirements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    content VARCHAR(300) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    KEY idx_course_requirements_course (course_id),
    CONSTRAINT fk_course_requirements_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_outcomes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    content VARCHAR(300) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    KEY idx_course_outcomes_course (course_id),
    CONSTRAINT fk_course_outcomes_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_faq (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    question VARCHAR(300) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    KEY idx_course_faq_course (course_id),
    CONSTRAINT fk_course_faq_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_chapters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    release_at DATETIME NULL,
    estimated_duration_minutes INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_course_chapters_uuid (uuid),
    KEY idx_course_chapters_course_order (course_id, sort_order),
    CONSTRAINT fk_course_chapters_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_lessons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    chapter_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    summary VARCHAR(500) NULL,
    content_type ENUM('video', 'text', 'markdown', 'pdf', 'slide', 'audio', 'mixed') NOT NULL DEFAULT 'video',
    content_body MEDIUMTEXT NULL COMMENT 'markdown/text body when applicable',
    video_media_id BIGINT UNSIGNED NULL,
    video_duration_seconds INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_preview TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'free preview lesson visible to non-enrolled users',
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    release_at DATETIME NULL,
    estimated_duration_minutes INT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_course_lessons_uuid (uuid),
    UNIQUE KEY uq_course_lessons_slug (course_id, slug),
    KEY idx_course_lessons_chapter_order (chapter_id, sort_order),
    KEY idx_course_lessons_course (course_id),
    FULLTEXT KEY ft_course_lessons_title_summary (title, summary),
    CONSTRAINT fk_course_lessons_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_lessons_chapter FOREIGN KEY (chapter_id) REFERENCES course_chapters (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_lessons_video_media FOREIGN KEY (video_media_id) REFERENCES media_files (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id BIGINT UNSIGNED NOT NULL,
    media_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(150) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    KEY idx_lesson_attachments_lesson (lesson_id),
    CONSTRAINT fk_lesson_attachments_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons (id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_attachments_media FOREIGN KEY (media_id) REFERENCES media_files (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NULL COMMENT 'nullable: free courses have no order',
    enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL COMMENT 'membership/time-limited access',
    completion_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    last_accessed_at DATETIME NULL,
    UNIQUE KEY uq_course_enrollments_uuid (uuid),
    UNIQUE KEY uq_course_enrollments_pair (course_id, student_id),
    KEY idx_course_enrollments_student (student_id),
    KEY idx_course_enrollments_course_published (course_id, enrolled_at),
    CONSTRAINT fk_course_enrollments_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_enrollments_student FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    lesson_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    watched_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    last_position_seconds INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'video resume timestamp',
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lesson_progress_pair (student_id, lesson_id),
    KEY idx_lesson_progress_course_student (course_id, student_id),
    CONSTRAINT fk_lesson_progress_student FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_progress_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons (id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_progress_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cross-content "continue learning" resume pointer (video timestamp, PDF
-- page, flashcard deck position, ...), synced across devices.
CREATE TABLE IF NOT EXISTS learning_resume_points (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    content_type ENUM('lesson_video', 'lesson_pdf', 'flashcard_deck', 'document', 'quiz') NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NULL,
    position_data JSON NOT NULL COMMENT 'e.g. {"seconds": 214} or {"page": 12} or {"card_index": 5}',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resume_points_pair (student_id, content_type, content_id),
    KEY idx_resume_points_student_updated (student_id, updated_at),
    CONSTRAINT fk_resume_points_student FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
