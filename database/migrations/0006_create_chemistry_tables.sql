-- ============================================================
-- Migration 0006: Chemistry Engine core
-- Depends on: 0001 (users, for created_by)
-- Per CHEMISTRY_DOMAIN.md, CHEMISTRY_DATABASE.md, CHEMISTRY_EDITOR.md
-- ============================================================

CREATE TABLE IF NOT EXISTS chemistry_elements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    atomic_number SMALLINT UNSIGNED NOT NULL,
    symbol VARCHAR(3) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    name_vi VARCHAR(50) NOT NULL,
    atomic_mass DECIMAL(10,4) NOT NULL,
    electron_configuration VARCHAR(100) NULL,
    period_number TINYINT UNSIGNED NULL,
    group_number TINYINT UNSIGNED NULL,
    block ENUM('s', 'p', 'd', 'f') NULL,
    category VARCHAR(50) NULL COMMENT 'alkali metal, noble gas, halogen, transition metal, ...',
    oxidation_states VARCHAR(50) NULL COMMENT 'comma separated, e.g. -1,+1,+3,+5,+7',
    electronegativity DECIMAL(4,2) NULL,
    density_g_cm3 DECIMAL(10,4) NULL,
    melting_point_c DECIMAL(10,2) NULL,
    boiling_point_c DECIMAL(10,2) NULL,
    appearance VARCHAR(150) NULL,
    discovered_by VARCHAR(150) NULL,
    discovery_year SMALLINT NULL,
    applications TEXT NULL,
    safety_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_elements_uuid (uuid),
    UNIQUE KEY uq_elements_atomic_number (atomic_number),
    UNIQUE KEY uq_elements_symbol (symbol),
    KEY idx_elements_group_period (group_number, period_number),
    FULLTEXT KEY ft_elements_names (name_en, name_vi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chemistry_compounds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    formula VARCHAR(100) NOT NULL COMMENT 'plain-text input form, e.g. H2SO4',
    formula_display VARCHAR(150) NOT NULL COMMENT 'normalized with unicode sub/superscript, e.g. H₂SO₄',
    name_vi VARCHAR(200) NOT NULL,
    name_en VARCHAR(200) NULL,
    common_name VARCHAR(200) NULL,
    category ENUM('acid', 'base', 'salt', 'oxide', 'organic', 'gas', 'metal', 'other') NOT NULL DEFAULT 'other',
    molar_mass_g_mol DECIMAL(10,3) NULL,
    physical_state ENUM('solid', 'liquid', 'gas', 'aqueous') NULL,
    color VARCHAR(100) NULL,
    solubility TEXT NULL,
    preparation TEXT NULL,
    applications TEXT NULL,
    hazards TEXT NULL,
    storage_notes TEXT NULL,
    is_organic TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_compounds_uuid (uuid),
    KEY idx_compounds_formula (formula),
    KEY idx_compounds_category (category),
    FULLTEXT KEY ft_compounds_names (name_vi, name_en, common_name, formula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alias/typo-tolerant lookup table: every alternate name a compound can be
-- searched by (Vietnamese, English, IUPAC, common/slang name).
CREATE TABLE IF NOT EXISTS chemistry_compound_aliases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compound_id BIGINT UNSIGNED NOT NULL,
    alias VARCHAR(200) NOT NULL,
    alias_normalized VARCHAR(200) NOT NULL COMMENT 'lowercase, accent-stripped, for typo-tolerant search',
    KEY idx_compound_aliases_compound (compound_id),
    KEY idx_compound_aliases_normalized (alias_normalized),
    CONSTRAINT fk_compound_aliases_compound FOREIGN KEY (compound_id) REFERENCES chemistry_compounds (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chemistry_reactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    reaction_name VARCHAR(200) NULL,
    equation_display VARCHAR(500) NOT NULL COMMENT 'normalized display form of the balanced equation',
    reaction_type ENUM(
        'synthesis', 'decomposition', 'single_replacement', 'double_replacement',
        'combustion', 'acid_base', 'redox', 'precipitation', 'organic', 'other'
    ) NOT NULL DEFAULT 'other',
    conditions VARCHAR(300) NULL COMMENT 'e.g. nhiệt độ, xúc tác',
    temperature VARCHAR(100) NULL,
    pressure VARCHAR(100) NULL,
    catalyst VARCHAR(150) NULL,
    color_change VARCHAR(200) NULL,
    gas_released VARCHAR(150) NULL,
    precipitate VARCHAR(150) NULL,
    net_ionic_equation VARCHAR(500) NULL,
    explanation TEXT NULL,
    applications TEXT NULL,
    safety_notes TEXT NULL,
    difficulty ENUM('very_easy', 'easy', 'medium', 'hard', 'very_hard') NOT NULL DEFAULT 'medium',
    grade_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_reactions_uuid (uuid),
    KEY idx_reactions_type (reaction_type),
    KEY idx_reactions_grade (grade_id),
    KEY idx_reactions_difficulty (difficulty),
    FULLTEXT KEY ft_reactions_name_equation (reaction_name, equation_display, explanation),
    CONSTRAINT fk_reactions_grade FOREIGN KEY (grade_id) REFERENCES grades (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactants/products with stoichiometric coefficients, linked to the
-- compound library so searches on a compound surface every reaction
-- it participates in.
CREATE TABLE IF NOT EXISTS chemistry_reaction_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reaction_id BIGINT UNSIGNED NOT NULL,
    compound_id BIGINT UNSIGNED NULL COMMENT 'null when the participant is a bare element, see element_id',
    element_id BIGINT UNSIGNED NULL,
    role ENUM('reactant', 'product') NOT NULL,
    coefficient SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    physical_state ENUM('solid', 'liquid', 'gas', 'aqueous') NULL,
    sort_order INT NOT NULL DEFAULT 0,
    KEY idx_reaction_participants_reaction (reaction_id),
    KEY idx_reaction_participants_compound (compound_id),
    CONSTRAINT fk_reaction_participants_reaction FOREIGN KEY (reaction_id) REFERENCES chemistry_reactions (id) ON DELETE CASCADE,
    CONSTRAINT fk_reaction_participants_compound FOREIGN KEY (compound_id) REFERENCES chemistry_compounds (id) ON DELETE CASCADE,
    CONSTRAINT fk_reaction_participants_element FOREIGN KEY (element_id) REFERENCES chemistry_elements (id) ON DELETE CASCADE,
    CONSTRAINT chk_reaction_participant_target CHECK (compound_id IS NOT NULL OR element_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chemistry_reaction_lessons (
    reaction_id BIGINT UNSIGNED NOT NULL,
    lesson_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (reaction_id, lesson_id),
    CONSTRAINT fk_reaction_lessons_reaction FOREIGN KEY (reaction_id) REFERENCES chemistry_reactions (id) ON DELETE CASCADE,
    CONSTRAINT fk_reaction_lessons_lesson FOREIGN KEY (lesson_id) REFERENCES course_lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formula Library (distinct from compounds: covers calculation/organic/ion
-- formula "recipes" used by the Formula Editor and quiz generator, e.g.
-- "molarity formula", "empirical formula method").
CREATE TABLE IF NOT EXISTS chemistry_formulas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    name VARCHAR(200) NOT NULL,
    formula_type ENUM('molecular', 'empirical', 'structural', 'ion', 'organic', 'calculation') NOT NULL,
    expression VARCHAR(300) NOT NULL COMMENT 'normalized display expression',
    description TEXT NULL,
    worked_example TEXT NULL,
    applications TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_formulas_uuid (uuid),
    KEY idx_formulas_type (formula_type),
    FULLTEXT KEY ft_formulas_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
