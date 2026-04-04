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
    target_type ENUM('user', 'task') NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_audit_admin
        FOREIGN KEY (admin_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_admin_audit_target (target_type, target_id)
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
