<?php

declare(strict_types=1);

final class AppSetting
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (!Database::tableExists('app_settings')) {
            return $default;
        }

        $statement = $this->db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :setting_key LIMIT 1');
        $statement->execute(['setting_key' => $key]);
        $row = $statement->fetch();

        return $row !== false ? (string) $row['setting_value'] : $default;
    }

    public function all(): array
    {
        if (!Database::tableExists('app_settings')) {
            return [];
        }

        $statement = $this->db->prepare('SELECT * FROM app_settings ORDER BY setting_key ASC');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function set(string $key, string $value): void
    {
        if (!Database::tableExists('app_settings')) {
            throw new RuntimeException('The app_settings table does not exist yet. Run the subscription migration first.');
        }

        $statement = $this->db->prepare('
            INSERT INTO app_settings (setting_key, setting_value, created_at, updated_at)
            VALUES (:setting_key, :setting_value, NOW(), NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ');
        $statement->execute([
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }
}
