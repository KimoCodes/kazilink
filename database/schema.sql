CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'tasker', 'admin') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_failed_login_at DATETIME NULL,
    last_login_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    last_logout_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    city VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    bio TEXT NULL,
    avatar_path VARCHAR(255) NULL,
    skills_summary TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_profiles_user_id (user_id)
);

CREATE TABLE tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    status ENUM('open', 'booked', 'completed', 'cancelled', 'deactivated') NOT NULL DEFAULT 'open',
    scheduled_for DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_client
        FOREIGN KEY (client_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_tasks_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT,
    KEY idx_tasks_status (status),
    KEY idx_tasks_category (category_id),
    KEY idx_tasks_city (city),
    KEY idx_tasks_client (client_id)
);

CREATE TABLE bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    tasker_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    message TEXT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bids_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bids_tasker
        FOREIGN KEY (tasker_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_bids_task_tasker (task_id, tasker_id),
    KEY idx_bids_status (status),
    KEY idx_bids_task (task_id),
    KEY idx_bids_tasker (tasker_id)
);

CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    bid_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    tasker_id INT UNSIGNED NOT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    booked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bookings_bid
        FOREIGN KEY (bid_id) REFERENCES bids(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bookings_client
        FOREIGN KEY (client_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bookings_tasker
        FOREIGN KEY (tasker_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_bookings_task_id (task_id),
    UNIQUE KEY uq_bookings_bid_id (bid_id),
    KEY idx_bookings_client (client_id),
    KEY idx_bookings_tasker (tasker_id),
    KEY idx_bookings_status (status)
);

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_messages_booking (booking_id),
    KEY idx_messages_created (created_at)
);

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    reviewee_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewee
        FOREIGN KEY (reviewee_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY uq_reviews_booking_reviewer (booking_id, reviewer_id),
    KEY idx_reviews_reviewee (reviewee_id)
);

CREATE TABLE admin_audit (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT UNSIGNED NOT NULL,
    target_type ENUM('user', 'task', 'dispute', 'plan', 'promo_code', 'subscription', 'momo_transaction', 'setting', 'momo_webhook_log') NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_audit_admin
        FOREIGN KEY (admin_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_admin_audit_target (target_type, target_id)
);

CREATE TABLE app_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE plans (
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

CREATE TABLE subscriptions (
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

CREATE TABLE promo_codes (
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

CREATE TABLE promo_code_users (
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

CREATE TABLE promo_redemptions (
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

CREATE TABLE momo_transactions (
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
    KEY idx_momo_transactions_created (created_at),
    KEY idx_momo_transactions_user_status (user_id, status)
);

CREATE TABLE momo_webhook_logs (
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

CREATE TABLE subscription_notifications (
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

CREATE TABLE payments (
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

CREATE TABLE hiring_agreements (
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
    KEY idx_hiring_agreements_tasker (tasker_user_id),
    KEY idx_hiring_agreements_booking_status (booking_id, status)
);

CREATE TABLE agreement_events (
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

CREATE TABLE disputes (
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

CREATE TABLE product_listings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL,
    starting_price DECIMAL(10,2) NOT NULL,
    status ENUM('open', 'sold', 'cancelled', 'deactivated') NOT NULL DEFAULT 'open',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_listings_seller
        FOREIGN KEY (seller_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_product_listings_status (status),
    KEY idx_product_listings_city (city),
    KEY idx_product_listings_seller (seller_id),
    KEY idx_product_listings_active_created (is_active, status, created_at, id)
);

CREATE TABLE product_bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id INT UNSIGNED NOT NULL,
    buyer_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    message TEXT NULL,
    status ENUM('pending', 'selected', 'rejected', 'withdrawn') NOT NULL DEFAULT 'pending',
    selected_listing_lock INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status = 'selected' THEN listing_id ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_bids_listing
        FOREIGN KEY (listing_id) REFERENCES product_listings(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_product_bids_buyer
        FOREIGN KEY (buyer_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_product_bids_listing_buyer (listing_id, buyer_id),
    UNIQUE KEY uq_product_bids_one_selected (selected_listing_lock),
    KEY idx_product_bids_status (status),
    KEY idx_product_bids_listing (listing_id),
    KEY idx_product_bids_buyer (buyer_id),
    KEY idx_product_bids_listing_status_amount (listing_id, status, amount, id)
);

CREATE TABLE ads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    media_type ENUM('image', 'video') NULL,
    media_path VARCHAR(255) NULL,
    cta_label VARCHAR(80) NULL,
    cta_url VARCHAR(255) NULL,
    placement ENUM('home', 'marketplace') NOT NULL DEFAULT 'home',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ads_placement (placement),
    KEY idx_ads_active (is_active),
    KEY idx_ads_sort (sort_order)
);
