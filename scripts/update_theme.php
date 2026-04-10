<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';

try {
    $db = Database::connection();
    $settings = [
        'theme_background_color' => '#f8fafc',
        'theme_surface_color' => '#ffffff',
        'theme_text_color' => '#0f172a',
        'theme_primary_color' => '#0d9488',
        'theme_secondary_color' => '#fcd34d',
        'theme_mode' => 'light'
    ];

    foreach ($settings as $key => $value) {
        $stmt = $db->prepare('
            INSERT INTO site_settings (setting_key, setting_value) 
            VALUES (:key, :val) 
            ON DUPLICATE KEY UPDATE setting_value = :val2
        ');
        $stmt->execute(['key' => $key, 'val' => $value, 'val2' => $value]);
    }
    echo "Theme updated.\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
