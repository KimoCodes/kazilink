ALTER TABLE admin_audit
    MODIFY target_type ENUM('user', 'task', 'dispute', 'plan', 'promo_code', 'subscription', 'momo_transaction', 'setting', 'momo_webhook_log', 'subscription_payment_intent') NOT NULL;

CREATE TABLE IF NOT EXISTS subscription_payment_intents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(120) NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    amount_expected_rwf INT UNSIGNED NOT NULL,
    amount_paid_rwf INT UNSIGNED NULL,
    momo_number_displayed VARCHAR(30) NOT NULL,
    payer_phone VARCHAR(30) NULL,
    screenshot_url VARCHAR(255) NULL,
    screenshot_hash CHAR(40) NULL,
    submitted_at DATETIME NULL,
    intended_activation_at DATETIME NOT NULL,
    deadline_at DATETIME NOT NULL,
    status ENUM('draft', 'submitted', 'pending_verification', 'approved', 'rejected', 'activated', 'expired') NOT NULL DEFAULT 'draft',
    is_late TINYINT(1) NOT NULL DEFAULT 0,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    rejection_reason VARCHAR(255) NULL,
    activated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_payment_intents_plan
        FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_subscription_payment_intents_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_subscription_payment_intents_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_subscription_payment_intents_reference (reference),
    KEY idx_subscription_payment_intents_user (user_id),
    KEY idx_subscription_payment_intents_status (status),
    KEY idx_subscription_payment_intents_activation (intended_activation_at),
    KEY idx_subscription_payment_intents_deadline (deadline_at),
    KEY idx_subscription_payment_intents_hash (screenshot_hash)
);

CREATE TABLE IF NOT EXISTS subscription_payment_intent_audit (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_intent_id INT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NULL,
    actor_type ENUM('user', 'admin', 'system') NOT NULL,
    action VARCHAR(100) NOT NULL,
    from_status VARCHAR(50) NULL,
    to_status VARCHAR(50) NULL,
    reason VARCHAR(255) NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_payment_intent_audit_intent
        FOREIGN KEY (payment_intent_id) REFERENCES subscription_payment_intents(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_subscription_payment_intent_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    KEY idx_subscription_payment_intent_audit_intent (payment_intent_id),
    KEY idx_subscription_payment_intent_audit_action (action),
    KEY idx_subscription_payment_intent_audit_created (created_at)
);
