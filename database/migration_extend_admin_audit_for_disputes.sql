ALTER TABLE admin_audit
    MODIFY COLUMN target_type ENUM('user', 'task', 'dispute') NOT NULL;
