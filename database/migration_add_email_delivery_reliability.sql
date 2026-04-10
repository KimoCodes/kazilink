CREATE TABLE IF NOT EXISTS email_recipients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    status ENUM('active', 'undeliverable', 'suppressed') NOT NULL DEFAULT 'active',
    last_bounce_at DATETIME NULL,
    last_complaint_at DATETIME NULL,
    last_failure_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_recipients_email (email),
    KEY idx_email_recipients_status (status)
);

CREATE TABLE IF NOT EXISTS email_outbox (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id INT UNSIGNED NOT NULL DEFAULT 0,
    recipient_email VARCHAR(190) NOT NULL,
    recipient_name VARCHAR(160) NULL,
    template_name VARCHAR(120) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    template_data_json LONGTEXT NOT NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    status ENUM('pending', 'processing', 'retry_scheduled', 'sent', 'failed_transient', 'failed_permanent', 'skipped') NOT NULL DEFAULT 'pending',
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    provider_message_id VARCHAR(190) NULL,
    last_error_code VARCHAR(120) NULL,
    last_error_message VARCHAR(255) NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    sent_at DATETIME NULL,
    UNIQUE KEY uq_email_outbox_idempotency (idempotency_key),
    KEY idx_email_outbox_status_due (status, next_attempt_at),
    KEY idx_email_outbox_event (event_name, entity_type, entity_id),
    KEY idx_email_outbox_recipient (recipient_email)
);

CREATE TABLE IF NOT EXISTS email_delivery_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    outbox_id INT UNSIGNED NOT NULL,
    attempt_number INT UNSIGNED NOT NULL,
    provider VARCHAR(80) NOT NULL,
    request_payload_json LONGTEXT NULL,
    response_payload_json LONGTEXT NULL,
    result ENUM('sent', 'failed_transient', 'failed_permanent') NOT NULL,
    error_code VARCHAR(120) NULL,
    error_message VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_delivery_attempts_outbox
        FOREIGN KEY (outbox_id) REFERENCES email_outbox(id)
        ON DELETE CASCADE,
    KEY idx_email_delivery_attempts_outbox (outbox_id),
    KEY idx_email_delivery_attempts_result (result)
);
