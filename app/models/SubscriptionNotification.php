<?php

declare(strict_types=1);

final class SubscriptionNotification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function queuePastDueReminder(int $userId, string $referenceKey, array $payload): bool
    {
        if (!Database::tableExists('subscription_notifications')) {
            return false;
        }

        $statement = $this->db->prepare('
            INSERT IGNORE INTO subscription_notifications (
                user_id, notification_type, channel, reference_key, status, payload_json, scheduled_for, sent_at, created_at, updated_at
            ) VALUES (
                :user_id, :notification_type, :channel, :reference_key, :status, :payload_json, NOW(), NULL, NOW(), NOW()
            )
        ');
        $statement->execute([
            'user_id' => $userId,
            'notification_type' => 'subscription_past_due_reminder',
            'channel' => 'email_stub',
            'reference_key' => $referenceKey,
            'status' => 'queued',
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return $statement->rowCount() > 0;
    }

    public function queued(int $limit = 50): array
    {
        if (!Database::tableExists('subscription_notifications')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM subscription_notifications
            WHERE status = :status
            ORDER BY scheduled_for ASC, id ASC
            LIMIT :limit
        ');
        $statement->bindValue(':status', 'queued');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function markSent(int $id): void
    {
        if (!Database::tableExists('subscription_notifications')) {
            return;
        }

        $statement = $this->db->prepare('
            UPDATE subscription_notifications
            SET status = :status, sent_at = NOW(), updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'status' => 'sent',
        ]);
    }
}
