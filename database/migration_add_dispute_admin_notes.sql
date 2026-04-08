ALTER TABLE disputes
    ADD COLUMN admin_notes TEXT NULL AFTER status,
    ADD COLUMN admin_updated_by INT UNSIGNED NULL AFTER admin_notes,
    ADD COLUMN resolved_at DATETIME NULL AFTER admin_updated_by,
    ADD CONSTRAINT fk_disputes_admin_updated_by FOREIGN KEY (admin_updated_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD KEY idx_disputes_admin_updated_by (admin_updated_by);
