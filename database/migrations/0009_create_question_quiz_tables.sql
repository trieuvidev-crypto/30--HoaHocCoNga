-- ============================================================
-- Migration 0009: Question Bank + Quiz Engine
-- Depends on: 0001 (users), 0003 (grades/subjects), 0005 (courses/lessons)
-- Per QUESTION_BANK.md and PROJECT.md §Quiz Tables
-- ============================================================

CREATE TABLE IF NOT EXISTS question_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_question_categories_uuid (uuid),
    UNIQUE KEY uq_question_categories_slug (slug),
    CONSTRAINT fk_question_categories_parent FOREIGN KEY (parent_id) REFERENCES question_categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_bank (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    question_type ENUM(
        'single_choice', 'multiple_choice', 'true_false', 'fill_blank',
        'matching', 'essay', 'calculation', 'chemical_equation',
        'chemical_formula', 'image_question'
    ) NOT NULL DEFAULT 'single_choice',
    title VARCHAR(500) NOT NULL COMMENT 'the question text itself',
    explanation TEXT NULL COMMENT 'shown after the student answers',
    hint VARCHAR(500) NULL,
    difficulty ENUM('very_easy', 'easy', 'medium', 'hard', 'very_hard') NOT NULL DEFAULT 'medium',
    grade_id BIGINT UNSIGNED NULL,
    subject_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    image_media_id BIGINT UNSIGNED NULL,
    -- For fill_blank/essay/calculation: the accepted answer as plain text.
    -- For single/multiple_choice/true_false: correctness lives on
    -- question_options.is_correct instead, this stays NULL.
    text_answer VARCHAR(500) NULL,
    default_points DECIMAL(6,2) NOT NULL DEFAULT 1.00,
    estimated_seconds INT UNSIGNED NULL,
    status ENUM('draft', 'review', 'approved', 'published', 'archived', 'deprecated') NOT NULL DEFAULT 'draft',
    usage_count INT UNSIGNED NOT NULL DEFAULT 0,
    correct_rate DECIMAL(5,2) NULL COMMENT 'recomputed periodically from attempt history',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_question_bank_uuid (uuid),
    KEY idx_question_bank_type (question_type),
    KEY idx_question_bank_difficulty (difficulty),
    KEY idx_question_bank_grade (grade_id),
    KEY idx_question_bank_category (category_id),
    KEY idx_question_bank_status (status),
    FULLTEXT KEY ft_question_bank_title (title),
    CONSTRAINT fk_question_bank_grade FOREIGN KEY (grade_id) REFERENCES grades (id) ON DELETE SET NULL,
    CONSTRAINT fk_question_bank_subject FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL,
    CONSTRAINT fk_question_bank_category FOREIGN KEY (category_id) REFERENCES question_categories (id) ON DELETE SET NULL,
    CONSTRAINT fk_question_bank_image FOREIGN KEY (image_media_id) REFERENCES media_files (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    UNIQUE KEY uq_question_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_tag_relations (
    question_id BIGINT UNSIGNED NOT NULL,
    question_tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (question_id, question_tag_id),
    CONSTRAINT fk_question_tag_relations_question FOREIGN KEY (question_id) REFERENCES question_bank (id) ON DELETE CASCADE,
    CONSTRAINT fk_question_tag_relations_tag FOREIGN KEY (question_tag_id) REFERENCES question_tags (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Answer options for single_choice/multiple_choice/true_false/matching.
CREATE TABLE IF NOT EXISTS question_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    content VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    KEY idx_question_options_question (question_id),
    CONSTRAINT fk_question_options_question FOREIGN KEY (question_id) REFERENCES question_bank (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    reported_by BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(500) NOT NULL,
    status ENUM('open', 'reviewed', 'dismissed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_question_reports_question (question_id),
    CONSTRAINT fk_question_reports_question FOREIGN KEY (question_id) REFERENCES question_bank (id) ON DELETE CASCADE,
    CONSTRAINT fk_question_reports_user FOREIGN KEY (reported_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- QUIZ ENGINE
-- ============================================================

CREATE TABLE IF NOT EXISTS quizzes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    description VARCHAR(500) NULL,
    quiz_type ENUM('practice', 'mini_quiz', 'midterm', 'final', 'mock_test', 'national_exam') NOT NULL DEFAULT 'practice',
    course_id BIGINT UNSIGNED NULL,
    lesson_id BIGINT UNSIGNED NULL,
    time_limit_minutes INT UNSIGNED NULL COMMENT 'NULL = untimed',
    passing_score DECIMAL(5,2) NULL COMMENT 'percentage required to pass, NULL = no pass/fail concept',
    shuffle_questions TINYINT(1) NOT NULL DEFAULT 1,
    shuffle_options TINYINT(1) NOT NULL DEFAULT 1,
    max_attempts INT UNSIGNED NULL COMMENT 'NULL = unlimited',
    show_correct_answers ENUM('immediately', 'after_submit', 'never') NOT NULL DEFAULT 'after_submit',
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_quizzes_uuid (uuid),
    UNIQUE KEY uq_quizzes_slug (slug),
    KEY idx_quizzes_course (course_id),
    KEY idx_quizzes_lesson (lesson_id),
    KEY idx_quizzes_status (status),
    CONSTRAINT fk_quizzes_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
    CONSTRAINT fk_quizzes_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons (id) ON DELETE CASCADE,
    CONSTRAINT fk_quizzes_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    points DECIMAL(6,2) NOT NULL DEFAULT 1.00,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_quiz_questions_pair (quiz_id, question_id),
    KEY idx_quiz_questions_quiz_order (quiz_id, sort_order),
    CONSTRAINT fk_quiz_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_questions_question FOREIGN KEY (question_id) REFERENCES question_bank (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    quiz_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    attempt_number INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('in_progress', 'submitted', 'expired', 'abandoned') NOT NULL DEFAULT 'in_progress',
    total_points DECIMAL(8,2) NULL,
    earned_points DECIMAL(8,2) NULL,
    score_percent DECIMAL(5,2) NULL,
    is_passed TINYINT(1) NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    expires_at DATETIME NULL COMMENT 'started_at + time_limit_minutes, NULL if untimed',
    UNIQUE KEY uq_quiz_attempts_uuid (uuid),
    KEY idx_quiz_attempts_quiz_student (quiz_id, student_id),
    KEY idx_quiz_attempts_status (status),
    CONSTRAINT fk_quiz_attempts_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempts_student FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempt_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    -- Selected option ids for choice questions, stored as JSON array of
    -- question_options.id (e.g. "[12]" or "[12,15]"); NULL for text answers.
    selected_option_ids JSON NULL,
    text_answer VARCHAR(1000) NULL COMMENT 'for fill_blank/essay/calculation',
    is_correct TINYINT(1) NULL COMMENT 'NULL until graded (essay may need manual grading)',
    points_earned DECIMAL(6,2) NULL,
    answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_quiz_attempt_answers_pair (attempt_id, question_id),
    KEY idx_quiz_attempt_answers_question (question_id),
    CONSTRAINT fk_quiz_attempt_answers_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts (id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempt_answers_question FOREIGN KEY (question_id) REFERENCES question_bank (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
