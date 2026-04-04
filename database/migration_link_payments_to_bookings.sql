ALTER TABLE payments
    ADD COLUMN booking_id INT UNSIGNED NULL AFTER user_id,
    ADD COLUMN task_id INT UNSIGNED NULL AFTER booking_id,
    ADD CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_payments_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    ADD KEY idx_payments_booking (booking_id),
    ADD KEY idx_payments_task (task_id);
