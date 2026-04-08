CREATE TABLE IF NOT EXISTS hiring_agreements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_uid VARCHAR(32) NOT NULL,
    booking_id INT UNSIGNED NOT NULL,
    task_id INT UNSIGNED NOT NULL,
    client_user_id INT UNSIGNED NOT NULL,
    tasker_user_id INT UNSIGNED NOT NULL,
    job_title VARCHAR(180) NOT NULL,
    job_description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    location_text VARCHAR(255) NOT NULL,
    start_datetime DATETIME NULL,
    expected_duration VARCHAR(120) NULL,
    offline_payment_terms_text TEXT NOT NULL,
    compensation_terms_text TEXT NOT NULL,
    cancellation_terms_text TEXT NOT NULL,
    dispute_window_hours INT UNSIGNED NOT NULL DEFAULT 48,
    status ENUM('draft', 'pending_acceptance', 'accepted', 'cancelled', 'disputed') NOT NULL DEFAULT 'pending_acceptance',
    client_accepted_at DATETIME NULL,
    tasker_accepted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hiring_agreements_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hiring_agreements_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hiring_agreements_client
        FOREIGN KEY (client_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hiring_agreements_tasker
        FOREIGN KEY (tasker_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_hiring_agreements_uid (agreement_uid),
    UNIQUE KEY uq_hiring_agreements_booking (booking_id),
    KEY idx_hiring_agreements_task (task_id),
    KEY idx_hiring_agreements_status (status),
    KEY idx_hiring_agreements_client (client_user_id),
    KEY idx_hiring_agreements_tasker (tasker_user_id)
);

CREATE TABLE IF NOT EXISTS agreement_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id INT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NULL,
    event_type VARCHAR(100) NOT NULL,
    event_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agreement_events_agreement
        FOREIGN KEY (agreement_id) REFERENCES hiring_agreements(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_agreement_events_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    KEY idx_agreement_events_agreement (agreement_id),
    KEY idx_agreement_events_event_type (event_type),
    KEY idx_agreement_events_created (created_at)
);

CREATE TABLE IF NOT EXISTS disputes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id INT UNSIGNED NOT NULL,
    reporter_user_id INT UNSIGNED NOT NULL,
    type ENUM('non_payment', 'client_unavailable', 'tasker_no_show', 'scope_change', 'unsafe', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'under_review', 'resolved', 'rejected') NOT NULL DEFAULT 'open',
    admin_notes TEXT NULL,
    admin_updated_by INT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_disputes_agreement
        FOREIGN KEY (agreement_id) REFERENCES hiring_agreements(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_disputes_reporter
        FOREIGN KEY (reporter_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_disputes_admin_updated_by
        FOREIGN KEY (admin_updated_by) REFERENCES users(id)
        ON DELETE SET NULL,
    KEY idx_disputes_agreement (agreement_id),
    KEY idx_disputes_reporter (reporter_user_id),
    KEY idx_disputes_admin_updated_by (admin_updated_by),
    KEY idx_disputes_status (status)
);
