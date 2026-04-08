ALTER TABLE admin_audit
    MODIFY target_type ENUM('user', 'task', 'dispute', 'plan', 'promo_code', 'subscription', 'momo_transaction', 'setting', 'momo_webhook_log') NOT NULL;

CREATE TABLE IF NOT EXISTS app_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    price_rwf INT UNSIGNED NOT NULL,
    visibility_level INT UNSIGNED NOT NULL DEFAULT 1,
    max_applications_per_day INT UNSIGNED NOT NULL DEFAULT 5,
    priority_level INT UNSIGNED NOT NULL DEFAULT 1,
    job_alert_delay_minutes INT NOT NULL DEFAULT 10,
    max_active_jobs INT UNSIGNED NOT NULL DEFAULT 1,
    commission_discount DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    badge_name VARCHAR(120) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_plans_active (active),
    KEY idx_plans_visibility (visibility_level),
    KEY idx_plans_priority (priority_level)
);

CREATE TABLE IF NOT EXISTS subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    active_plan_id INT UNSIGNED NULL,
    pending_plan_id INT UNSIGNED NULL,
    status ENUM('trialing', 'active', 'past_due', 'cancelled') NOT NULL DEFAULT 'trialing',
    trial_ends_at DATETIME NULL,
    current_period_ends_at DATETIME NULL,
    momo_reference VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscriptions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_subscriptions_plan
        FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_subscriptions_active_plan
        FOREIGN KEY (active_plan_id) REFERENCES plans(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_subscriptions_pending_plan
        FOREIGN KEY (pending_plan_id) REFERENCES plans(id)
        ON DELETE SET NULL,
    KEY idx_subscriptions_user (user_id),
    KEY idx_subscriptions_status (status),
    KEY idx_subscriptions_period_end (current_period_ends_at),
    KEY idx_subscriptions_trial_end (trial_ends_at),
    KEY idx_subscriptions_active_plan (active_plan_id),
    KEY idx_subscriptions_pending_plan (pending_plan_id)
);

CREATE TABLE IF NOT EXISTS promo_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    type ENUM('percent', 'fixed_rwf') NOT NULL,
    amount INT UNSIGNED NOT NULL,
    max_redemptions INT UNSIGNED NULL,
    expires_at DATETIME NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_promo_codes_active (active),
    KEY idx_promo_codes_expires (expires_at)
);

CREATE TABLE IF NOT EXISTS promo_code_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_code_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_promo_code_users_code
        FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_promo_code_users_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_promo_code_users_code_user (promo_code_id, user_id),
    KEY idx_promo_code_users_user (user_id)
);

CREATE TABLE IF NOT EXISTS promo_redemptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_code_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    redeemed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_promo_redemptions_code
        FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_promo_redemptions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_promo_redemptions_code_user (promo_code_id, user_id),
    KEY idx_promo_redemptions_user (user_id)
);

CREATE TABLE IF NOT EXISTS momo_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    purpose ENUM('subscription') NOT NULL DEFAULT 'subscription',
    amount_rwf INT UNSIGNED NOT NULL,
    external_ref VARCHAR(120) NOT NULL,
    status ENUM('pending', 'successful', 'failed') NOT NULL DEFAULT 'pending',
    raw_payload_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_momo_transactions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_momo_transactions_external_ref (external_ref),
    KEY idx_momo_transactions_user (user_id),
    KEY idx_momo_transactions_status (status),
    KEY idx_momo_transactions_created (created_at)
);

CREATE TABLE IF NOT EXISTS momo_webhook_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_ref VARCHAR(120) NULL,
    request_ip VARCHAR(120) NOT NULL,
    decision ENUM('accepted', 'blocked_secret', 'blocked_ip', 'invalid_payload') NOT NULL,
    headers_json LONGTEXT NULL,
    payload_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_momo_webhook_logs_ref (external_ref),
    KEY idx_momo_webhook_logs_decision (decision),
    KEY idx_momo_webhook_logs_created (created_at)
);

CREATE TABLE IF NOT EXISTS subscription_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    notification_type ENUM('subscription_past_due_reminder') NOT NULL,
    channel ENUM('email_stub') NOT NULL DEFAULT 'email_stub',
    reference_key VARCHAR(120) NOT NULL,
    status ENUM('queued', 'sent') NOT NULL DEFAULT 'queued',
    payload_json LONGTEXT NULL,
    scheduled_for DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_subscription_notifications_unique (user_id, notification_type, reference_key),
    KEY idx_subscription_notifications_status (status),
    KEY idx_subscription_notifications_scheduled (scheduled_for)
);

CREATE TABLE IF NOT EXISTS user_metrics (
    user_id INT UNSIGNED PRIMARY KEY,
    daily_applications_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_reset_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_metrics_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

ALTER TABLE plans
    ADD COLUMN IF NOT EXISTS max_applications_per_day INT UNSIGNED NOT NULL DEFAULT 5 AFTER visibility_level,
    ADD COLUMN IF NOT EXISTS priority_level INT UNSIGNED NOT NULL DEFAULT 1 AFTER max_applications_per_day,
    ADD COLUMN IF NOT EXISTS job_alert_delay_minutes INT NOT NULL DEFAULT 10 AFTER priority_level,
    ADD COLUMN IF NOT EXISTS max_active_jobs INT UNSIGNED NOT NULL DEFAULT 1 AFTER job_alert_delay_minutes,
    ADD COLUMN IF NOT EXISTS commission_discount DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER max_active_jobs,
    ADD COLUMN IF NOT EXISTS badge_name VARCHAR(120) NULL AFTER commission_discount;

ALTER TABLE subscriptions
    ADD COLUMN IF NOT EXISTS active_plan_id INT UNSIGNED NULL AFTER plan_id,
    ADD COLUMN IF NOT EXISTS pending_plan_id INT UNSIGNED NULL AFTER active_plan_id;

UPDATE subscriptions
SET active_plan_id = COALESCE(active_plan_id, plan_id)
WHERE active_plan_id IS NULL;

SET @subscriptions_active_plan_idx = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'subscriptions'
              AND index_name = 'idx_subscriptions_active_plan'
        ),
        'SELECT 1',
        'ALTER TABLE subscriptions ADD KEY idx_subscriptions_active_plan (active_plan_id)'
    )
);
PREPARE subscriptions_active_plan_idx_stmt FROM @subscriptions_active_plan_idx;
EXECUTE subscriptions_active_plan_idx_stmt;
DEALLOCATE PREPARE subscriptions_active_plan_idx_stmt;

SET @subscriptions_pending_plan_idx = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'subscriptions'
              AND index_name = 'idx_subscriptions_pending_plan'
        ),
        'SELECT 1',
        'ALTER TABLE subscriptions ADD KEY idx_subscriptions_pending_plan (pending_plan_id)'
    )
);
PREPARE subscriptions_pending_plan_idx_stmt FROM @subscriptions_pending_plan_idx;
EXECUTE subscriptions_pending_plan_idx_stmt;
DEALLOCATE PREPARE subscriptions_pending_plan_idx_stmt;

INSERT INTO user_metrics (user_id, daily_applications_count, last_reset_date, created_at, updated_at)
SELECT u.id, 0, CURDATE(), NOW(), NOW()
FROM users u
LEFT JOIN user_metrics um ON um.user_id = u.id
WHERE um.user_id IS NULL;
