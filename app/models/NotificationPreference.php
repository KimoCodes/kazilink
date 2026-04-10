<?php

declare(strict_types=1);

final class NotificationPreference
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forUser(int $userId): array
    {
        if (!Database::tableExists('notification_preferences')) {
            return $this->defaults();
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM notification_preferences
            WHERE user_id = :user_id
            LIMIT 1
        ');
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : $this->defaults();
    }

    private function defaults(): array
    {
        return [
            'user_email_enabled' => 1,
            'user_inapp_enabled' => 1,
            'admin_email_enabled' => 1,
            'admin_inapp_enabled' => 1,
        ];
    }
}

