ALTER TABLE ads
    ADD COLUMN media_type ENUM('image', 'video') NULL AFTER body,
    ADD COLUMN media_path VARCHAR(255) NULL AFTER media_type;
