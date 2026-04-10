<?php

declare(strict_types=1);

final class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): bool
    {
        if (!Database::tableExists('notifications')) {
            return false;
        }

        $statement = $this->db->prepare('
            INSERT IGNORE INTO notifications (
                recipient_type,
                recipient_id,
                channel,
                title,
                body,
                link_url,
                event_name,
                event_id,
                status,
                template_name,
                template_data_json,
                attempt_count,
                created_at,
                sent_at,
                failure_reason
            )
            VALUES (
                :recipient_type,
                :recipient_id,
                :channel,
                :title,
                :body,
                :link_url,
                :event_name,
                :event_id,
                :status,
                :template_name,
                :template_data_json,
                :attempt_count,
                NOW(),
                :sent_at,
                :failure_reason
            )
        ');
        $statement->execute([
            'recipient_type' => $data['recipient_type'],
            'recipient_id' => $data['recipient_id'],
            'channel' => $data['channel'],
            'title' => $data['title'],
            'body' => $data['body'],
            'link_url' => $data['link_url'] ?? null,
            'event_name' => $data['event_name'],
            'event_id' => $data['event_id'],
            'status' => $data['status'],
            'template_name' => $data['template_name'] ?? null,
            'template_data_json' => $data['template_data_json'] ?? null,
            'attempt_count' => $data['attempt_count'] ?? 0,
            'sent_at' => $data['sent_at'] ?? null,
            'failure_reason' => $data['failure_reason'] ?? null,
        ]);

        return $statement->rowCount() > 0;
    }

    public function forRecipient(string $recipientType, int $recipientId, int $limit = 50): array
    {
        if (!Database::tableExists('notifications')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM notifications
            WHERE recipient_type = :recipient_type
              AND recipient_id = :recipient_id
              AND channel = :channel
            ORDER BY created_at DESC, id DESC
            LIMIT :limit
        ');
        $statement->bindValue(':recipient_type', $recipientType);
        $statement->bindValue(':recipient_id', $recipientId, PDO::PARAM_INT);
        $statement->bindValue(':channel', 'in_app');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function unreadCount(string $recipientType, int $recipientId): int
    {
        if (!Database::tableExists('notifications')) {
            return 0;
        }

        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM notifications
            WHERE recipient_type = :recipient_type
              AND recipient_id = :recipient_id
              AND channel = :channel
              AND status = :status
        ');
        $statement->execute([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'channel' => 'in_app',
            'status' => 'unread',
        ]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function markRead(int $id, string $recipientType, int $recipientId): void
    {
        if (!Database::tableExists('notifications')) {
            return;
        }

        $statement = $this->db->prepare('
            UPDATE notifications
            SET status = :read_status
            WHERE id = :id
              AND recipient_type = :recipient_type
              AND recipient_id = :recipient_id
              AND channel = :channel
              AND status = :unread_status
        ');
        $statement->execute([
            'id' => $id,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'channel' => 'in_app',
            'read_status' => 'read',
            'unread_status' => 'unread',
        ]);
    }

    public function queuedEmails(int $limit = 50): array
    {
        if (!Database::tableExists('notifications')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT n.*, u.email, p.full_name
            FROM notifications n
            INNER JOIN users u ON u.id = n.recipient_id
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE n.channel = :channel
              AND (n.status = :queued OR n.status = :failed)
              AND n.attempt_count < 3
              AND (n.failure_reason IS NULL OR n.failure_reason <> :invalid_recipient_email)
            ORDER BY n.created_at ASC, n.id ASC
            LIMIT :limit
        ');
        $statement->bindValue(':channel', 'email');
        $statement->bindValue(':queued', 'queued');
        $statement->bindValue(':failed', 'failed');
        $statement->bindValue(':invalid_recipient_email', 'invalid_recipient_email');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function markEmailSent(int $id): void
    {
        $statement = $this->db->prepare('
            UPDATE notifications
            SET status = :status,
                sent_at = NOW(),
                failure_reason = NULL
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'status' => 'sent',
        ]);
    }

    public function markEmailFailed(int $id, string $reason, bool $terminal = false): void
    {
        $attemptExpression = $terminal ? 3 : 'attempt_count + 1';
        $statement = $this->db->prepare('
            UPDATE notifications
            SET status = :status,
                attempt_count = ' . $attemptExpression . ',
                failure_reason = :failure_reason
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'status' => 'failed',
            'failure_reason' => mb_substr($reason, 0, 255),
        ]);
    }
}
