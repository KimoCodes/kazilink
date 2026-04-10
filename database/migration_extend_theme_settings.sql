INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('theme_background_color', '#f7f3ee'),
    ('theme_surface_color', '#fffdf9'),
    ('theme_text_color', '#1c1712'),
    ('theme_font_preset', 'inter'),
    ('theme_spacing_scale', 'normal')
ON DUPLICATE KEY UPDATE
    setting_value = setting_value;
