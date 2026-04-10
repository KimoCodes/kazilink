CREATE TABLE IF NOT EXISTS notification_events_outbox (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(80) NOT NULL,
    event_name VARCHAR(80) NOT NULL,
    idempotency_key VARCHAR(160) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    UNIQUE KEY uq_notification_events_outbox_event_id (event_id),
    UNIQUE KEY uq_notification_events_outbox_idempotency (idempotency_key),
    KEY idx_notification_events_outbox_processed (processed_at),
    KEY idx_notification_events_outbox_name (event_name)
);

CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    user_email_enabled TINYINT(1) NOT NULL DEFAULT 1,
    user_inapp_enabled TINYINT(1) NOT NULL DEFAULT 1,
    admin_email_enabled TINYINT(1) NOT NULL DEFAULT 1,
    admin_inapp_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_preferences_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_notification_preferences_user (user_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_type ENUM('user', 'admin') NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    channel ENUM('in_app', 'email') NOT NULL,
    title VARCHAR(80) NOT NULL,
    body TEXT NOT NULL,
    link_url VARCHAR(255) NULL,
    event_name VARCHAR(80) NOT NULL,
    event_id VARCHAR(80) NOT NULL,
    status ENUM('queued', 'sent', 'failed', 'unread', 'read') NOT NULL,
    template_name VARCHAR(120) NULL,
    template_data_json LONGTEXT NULL,
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    failure_reason VARCHAR(255) NULL,
    CONSTRAINT fk_notifications_recipient
        FOREIGN KEY (recipient_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_notifications_dedupe (event_name, event_id, recipient_type, recipient_id, channel),
    KEY idx_notifications_recipient (recipient_type, recipient_id),
    KEY idx_notifications_status (status),
    KEY idx_notifications_created (created_at)
);
