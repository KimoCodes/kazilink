ALTER TABLE users
    ADD COLUMN last_seen_at DATETIME NULL AFTER last_login_at,
    ADD COLUMN last_logout_at DATETIME NULL AFTER last_seen_at;
