CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    booking_id INT UNSIGNED NULL,
    task_id INT UNSIGNED NULL,
    plan_id VARCHAR(60) NOT NULL,
    plan_name VARCHAR(160) NOT NULL,
    amount_minor BIGINT UNSIGNED NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'rwf',
    status ENUM('pending', 'paid', 'failed', 'cancelled', 'expired', 'refunded', 'unknown') NOT NULL DEFAULT 'pending',
    checkout_status VARCHAR(40) NULL,
    stripe_payment_status VARCHAR(40) NULL,
    checkout_session_id VARCHAR(255) NOT NULL,
    payment_intent_id VARCHAR(255) NULL,
    stripe_customer_id VARCHAR(255) NULL,
    customer_email VARCHAR(190) NULL,
    last_event_id VARCHAR(255) NULL,
    last_event_type VARCHAR(120) NULL,
    metadata_json LONGTEXT NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_payments_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_payments_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_payments_checkout_session (checkout_session_id),
    UNIQUE KEY uq_payments_payment_intent (payment_intent_id),
    KEY idx_payments_user (user_id),
    KEY idx_payments_booking (booking_id),
    KEY idx_payments_task (task_id),
    KEY idx_payments_status (status),
    KEY idx_payments_created (created_at)
);
