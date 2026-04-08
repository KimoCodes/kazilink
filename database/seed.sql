INSERT IGNORE INTO categories (name, slug) VALUES
('Cleaning', 'cleaning'),
('Handyman', 'handyman'),
('Moving Help', 'moving-help'),
('Furniture Assembly', 'furniture-assembly'),
('Yard Work', 'yard-work');

INSERT IGNORE INTO users (email, password_hash, role, is_active, failed_login_attempts, last_failed_login_at, last_login_at, last_seen_at, last_logout_at, created_at, updated_at) VALUES
('admin@example.com', '$2y$10$o/nxyOqxV4gSDRs8R3xSh.7BMY1ajFeKbhwKrsTHIXm2L3S5st/s2', 'admin', 1, 0, NULL, NULL, NULL, NULL, NOW(), NOW()),
('client@example.com', '$2y$10$T.QDMVlvGBI4e8sbZrZJCePLMu51G0e84w1LLHvqmGy2o2wFG/9Ju', 'client', 1, 0, NULL, NULL, NULL, NULL, NOW(), NOW()),
('tasker@example.com', '$2y$10$T.QDMVlvGBI4e8sbZrZJCePLMu51G0e84w1LLHvqmGy2o2wFG/9Ju', 'tasker', 1, 0, NULL, NULL, NULL, NULL, NOW(), NOW());

INSERT IGNORE INTO profiles (user_id, full_name, phone, city, region, country, bio, avatar_path, skills_summary, created_at, updated_at) VALUES
((SELECT id FROM users WHERE email = 'admin@example.com'), 'Admin User', NULL, 'Kigali', NULL, 'Rwanda', 'Marketplace administrator.', NULL, NULL, NOW(), NOW()),
((SELECT id FROM users WHERE email = 'client@example.com'), 'Demo Client', NULL, 'Kigali', NULL, 'Rwanda', 'Posts household and moving tasks.', NULL, NULL, NOW(), NOW()),
((SELECT id FROM users WHERE email = 'tasker@example.com'), 'Demo Tasker', NULL, 'Kigali', NULL, 'Rwanda', 'Available for local service jobs.', NULL, 'Cleaning, moving help, furniture assembly', NOW(), NOW());

INSERT INTO plans (
    slug,
    name,
    price_rwf,
    visibility_level,
    max_applications_per_day,
    priority_level,
    job_alert_delay_minutes,
    max_active_jobs,
    commission_discount,
    badge_name,
    active,
    created_at,
    updated_at
) VALUES
('basic', 'Basic Trial', 500, 1, 5, 1, 10, 1, 0.00, NULL, 1, NOW(), NOW()),
('growth', 'Growth Trial', 3000, 2, 20, 2, 0, 5, 0.00, 'Rising Tasker', 1, NOW(), NOW()),
('pro', 'Pro Trial', 10000, 3, 9999, 3, -5, 9999, 10.00, 'Verified Pro', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    price_rwf = VALUES(price_rwf),
    visibility_level = VALUES(visibility_level),
    max_applications_per_day = VALUES(max_applications_per_day),
    priority_level = VALUES(priority_level),
    job_alert_delay_minutes = VALUES(job_alert_delay_minutes),
    max_active_jobs = VALUES(max_active_jobs),
    commission_discount = VALUES(commission_discount),
    badge_name = VALUES(badge_name),
    active = VALUES(active),
    updated_at = NOW();

INSERT IGNORE INTO app_settings (setting_key, setting_value, created_at, updated_at) VALUES
('subscription_grace_days', '5', NOW(), NOW());

INSERT IGNORE INTO subscriptions (user_id, plan_id, status, trial_ends_at, current_period_ends_at, momo_reference, created_at, updated_at) VALUES
((SELECT id FROM users WHERE email = 'client@example.com'), (SELECT id FROM plans WHERE slug = 'basic'), 'trialing', DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), NULL, NOW(), NOW()),
((SELECT id FROM users WHERE email = 'tasker@example.com'), (SELECT id FROM plans WHERE slug = 'basic'), 'trialing', DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), NULL, NOW(), NOW());

INSERT IGNORE INTO user_metrics (user_id, daily_applications_count, last_reset_date, created_at, updated_at) VALUES
((SELECT id FROM users WHERE email = 'client@example.com'), 0, CURDATE(), NOW(), NOW()),
((SELECT id FROM users WHERE email = 'tasker@example.com'), 0, CURDATE(), NOW(), NOW());

DELETE FROM tasks WHERE client_id = (SELECT id FROM users WHERE email = 'client@example.com');

INSERT INTO tasks (client_id, category_id, title, description, city, region, country, budget, status, scheduled_for, is_active, created_at, updated_at) VALUES
(
    (SELECT id FROM users WHERE email = 'client@example.com'),
    (SELECT id FROM categories WHERE slug = 'cleaning'),
    'Clean a two-bedroom apartment',
    'Need help with a standard deep clean for a two-bedroom apartment before guests arrive this weekend.',
    'Kigali',
    NULL,
    'Rwanda',
    50000.00,
    'open',
    DATE_ADD(NOW(), INTERVAL 3 DAY),
    1,
    NOW(),
    NOW()
),
(
    (SELECT id FROM users WHERE email = 'client@example.com'),
    (SELECT id FROM categories WHERE slug = 'furniture-assembly'),
    'Assemble a desk and office chair',
    'Looking for someone to assemble one flat-pack desk and one office chair in my home office.',
    'Kigali',
    NULL,
    'Rwanda',
    30000.00,
    'open',
    DATE_ADD(NOW(), INTERVAL 5 DAY),
    1,
    NOW(),
    NOW()
);

-- Test data for notification system
INSERT INTO bids (task_id, tasker_id, amount, message, status, created_at, updated_at) VALUES
(
    (SELECT id FROM tasks WHERE title = 'Clean a two-bedroom apartment'),
    (SELECT id FROM users WHERE email = 'tasker@example.com'),
    42.00,
    'I can do this cleaning job. I have all the supplies and experience.',
    'pending',
    NOW(),
    NOW()
),
(
    (SELECT id FROM tasks WHERE title = 'Assemble a desk and office chair'),
    (SELECT id FROM users WHERE email = 'tasker@example.com'),
    32.00,
    'Happy to assemble your furniture. I have the tools needed.',
    'pending',
    NOW(),
    NOW()
);

INSERT INTO bookings (task_id, bid_id, client_id, tasker_id, status, created_at, updated_at) VALUES
(
    (SELECT id FROM tasks WHERE title = 'Clean a two-bedroom apartment'),
    (SELECT id FROM bids WHERE task_id = (SELECT id FROM tasks WHERE title = 'Clean a two-bedroom apartment') AND status = 'pending'),
    (SELECT id FROM users WHERE email = 'client@example.com'),
    (SELECT id FROM users WHERE email = 'tasker@example.com'),
    'active',
    NOW(),
    NOW()
);

INSERT INTO messages (booking_id, sender_id, body, created_at) VALUES
(
    (SELECT id FROM bookings WHERE status = 'active'),
    (SELECT id FROM users WHERE email = 'tasker@example.com'),
    'Hi! I''ve been assigned to clean your apartment. When would be a good time to start?',
    NOW()
),
(
    (SELECT id FROM bookings WHERE status = 'active'),
    (SELECT id FROM users WHERE email = 'tasker@example.com'),
    'I''ll bring all my cleaning supplies and equipment. Should take about 2-3 hours.',
    NOW()
);

-- Demo credentials:
-- admin@example.com / admin12345
-- client@example.com / password123
-- tasker@example.com / password123
