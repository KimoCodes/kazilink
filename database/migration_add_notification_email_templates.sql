ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS template_name VARCHAR(120) NULL AFTER status,
    ADD COLUMN IF NOT EXISTS template_data_json LONGTEXT NULL AFTER template_name;
