CREATE TABLE IF NOT EXISTS site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL
);

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('bg_login', ''),
    ('bg_home', ''),
    ('bg_dashboard', ''),
    ('theme_background_color', '#f7f3ee'),
    ('theme_surface_color', '#fffdf9'),
    ('theme_text_color', '#1c1712'),
    ('theme_primary_color', '#8e7558'),
    ('theme_secondary_color', '#e9dfd2'),
    ('theme_mode', 'light'),
    ('theme_font_preset', 'inter'),
    ('theme_spacing_scale', 'normal')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);
