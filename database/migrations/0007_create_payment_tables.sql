-- ============================================================
-- Migration 0007: Payment domain
-- Depends on: 0001 (users), 0005 (courses)
-- Only the Bank QR + manual confirmation driver is wired to real
-- application logic in this phase (see config/payment.php).
-- ============================================================

CREATE TABLE IF NOT EXISTS coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    code VARCHAR(50) NOT NULL,
    discount_type ENUM('percentage', 'fixed_amount') NOT NULL,
    discount_value DECIMAL(12,2) NOT NULL,
    max_discount_amount DECIMAL(12,2) NULL,
    min_order_amount DECIMAL(12,2) NULL DEFAULT 0,
    usage_limit INT UNSIGNED NULL,
    usage_limit_per_user INT UNSIGNED NULL DEFAULT 1,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at DATETIME NULL,
    expires_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coupons_uuid (uuid),
    UNIQUE KEY uq_coupons_code (code),
    KEY idx_coupons_active_expiry (is_active, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupon_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_coupon_usage_coupon (coupon_id),
    KEY idx_coupon_usage_user (user_id),
    CONSTRAINT fk_coupon_usage_coupon FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE CASCADE,
    CONSTRAINT fk_coupon_usage_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    order_number VARCHAR(30) NOT NULL COMMENT 'e.g. HHCN-20260703-0001',
    user_id BIGINT UNSIGNED NOT NULL,
    subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    coupon_id BIGINT UNSIGNED NULL,
    status ENUM(
        'draft', 'pending', 'waiting_payment', 'paid', 'expired',
        'cancelled', 'refund_requested', 'refunded', 'failed', 'completed'
    ) NOT NULL DEFAULT 'draft',
    notes VARCHAR(500) NULL,
    expires_at DATETIME NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_orders_uuid (uuid),
    UNIQUE KEY uq_orders_number (order_number),
    KEY idx_orders_user (user_id),
    KEY idx_orders_status_created (status, created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    item_type ENUM('course', 'document', 'membership', 'bundle') NOT NULL DEFAULT 'course',
    course_id BIGINT UNSIGNED NULL,
    title_snapshot VARCHAR(200) NOT NULL COMMENT 'denormalized at purchase time so price history stays intact',
    unit_price DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    line_total DECIMAL(12,2) NOT NULL,
    UNIQUE KEY uq_order_items_uuid (uuid),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_course (course_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE course_enrollments
    ADD CONSTRAINT fk_course_enrollments_order_item FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE SET NULL;

-- One row per configured receiving bank account (admin-managed); the
-- active driver reads from here to render the VietQR-format QR code.
CREATE TABLE IF NOT EXISTS payment_bank_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(150) NOT NULL,
    bank_bin VARCHAR(10) NOT NULL COMMENT 'VietQR bank bin code',
    account_number VARCHAR(50) NOT NULL,
    account_holder VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    driver VARCHAR(30) NOT NULL DEFAULT 'bank_qr',
    transaction_number VARCHAR(50) NOT NULL COMMENT 'internal reference embedded in the QR transfer content',
    bank_account_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    status ENUM(
        'pending', 'processing', 'paid', 'partially_paid', 'cancelled',
        'expired', 'refunded', 'verification_failed', 'duplicate'
    ) NOT NULL DEFAULT 'pending',
    qr_payload TEXT NULL COMMENT 'raw EMV QR string generated for this payment',
    submitted_proof_media_id BIGINT UNSIGNED NULL COMMENT 'student-uploaded transfer receipt screenshot, if provided',
    verified_by BIGINT UNSIGNED NULL COMMENT 'admin/staff who manually confirmed the bank transfer',
    verified_at DATETIME NULL,
    verification_note VARCHAR(300) NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payments_uuid (uuid),
    UNIQUE KEY uq_payments_transaction_number (transaction_number),
    KEY idx_payments_order (order_id),
    KEY idx_payments_status (status),
    KEY idx_payments_expires (expires_at),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_bank_account FOREIGN KEY (bank_account_id) REFERENCES payment_bank_accounts (id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_proof_media FOREIGN KEY (submitted_proof_media_id) REFERENCES media_files (id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_verified_by FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT UNSIGNED NOT NULL,
    event VARCHAR(50) NOT NULL COMMENT 'created, proof_submitted, manually_verified, expired, refunded, ...',
    actor_user_id BIGINT UNSIGNED NULL,
    context JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_payment_logs_payment (payment_id),
    KEY idx_payment_logs_created_at (created_at),
    CONSTRAINT fk_payment_logs_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refund_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(500) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'refunded') NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    admin_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_refund_requests_uuid (uuid),
    KEY idx_refund_requests_order (order_id),
    KEY idx_refund_requests_status (status),
    CONSTRAINT fk_refund_requests_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_refund_requests_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE CASCADE,
    CONSTRAINT fk_refund_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_refund_requests_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    invoice_number VARCHAR(30) NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    issued_to_name VARCHAR(150) NOT NULL,
    issued_to_email VARCHAR(190) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    pdf_media_id BIGINT UNSIGNED NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invoices_uuid (uuid),
    UNIQUE KEY uq_invoices_number (invoice_number),
    KEY idx_invoices_order (order_id),
    CONSTRAINT fk_invoices_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_invoices_pdf_media FOREIGN KEY (pdf_media_id) REFERENCES media_files (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
