ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS booking_id INT UNSIGNED NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS task_id INT UNSIGNED NULL AFTER booking_id;

SET @payments_booking_idx = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'payments'
              AND index_name = 'idx_payments_booking'
        ),
        'SELECT 1',
        'ALTER TABLE payments ADD KEY idx_payments_booking (booking_id)'
    )
);
PREPARE payments_booking_idx_stmt FROM @payments_booking_idx;
EXECUTE payments_booking_idx_stmt;
DEALLOCATE PREPARE payments_booking_idx_stmt;

SET @payments_task_idx = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'payments'
              AND index_name = 'idx_payments_task'
        ),
        'SELECT 1',
        'ALTER TABLE payments ADD KEY idx_payments_task (task_id)'
    )
);
PREPARE payments_task_idx_stmt FROM @payments_task_idx;
EXECUTE payments_task_idx_stmt;
DEALLOCATE PREPARE payments_task_idx_stmt;

SET @payments_booking_fk = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_schema = DATABASE()
              AND table_name = 'payments'
              AND constraint_name = 'fk_payments_booking'
              AND constraint_type = 'FOREIGN KEY'
        ),
        'SELECT 1',
        'ALTER TABLE payments ADD CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL'
    )
);
PREPARE payments_booking_fk_stmt FROM @payments_booking_fk;
EXECUTE payments_booking_fk_stmt;
DEALLOCATE PREPARE payments_booking_fk_stmt;

SET @payments_task_fk = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_schema = DATABASE()
              AND table_name = 'payments'
              AND constraint_name = 'fk_payments_task'
              AND constraint_type = 'FOREIGN KEY'
        ),
        'SELECT 1',
        'ALTER TABLE payments ADD CONSTRAINT fk_payments_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL'
    )
);
PREPARE payments_task_fk_stmt FROM @payments_task_fk;
EXECUTE payments_task_fk_stmt;
DEALLOCATE PREPARE payments_task_fk_stmt;
