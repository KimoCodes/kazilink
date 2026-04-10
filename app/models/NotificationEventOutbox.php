<?php

declare(strict_types=1);

final class NotificationEventOutbox
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(string $eventName, string $idempotencyKey, array $payload): ?string
    {
        if (!Database::tableExists('notification_events_outbox')) {
            return null;
        }

        $eventId = sha1($eventName . '|' . $idempotencyKey);
        $statement = $this->db->prepare('
            INSERT IGNORE INTO notification_events_outbox (event_id, event_name, idempotency_key, payload_json, created_at, processed_at)
            VALUES (:event_id, :event_name, :idempotency_key, :payload_json, NOW(), NULL)
        ');
        $statement->execute([
            'event_id' => $eventId,
            'event_name' => $eventName,
            'idempotency_key' => $idempotencyKey,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        return $eventId;
    }

    public function pending(int $limit = 100): array
    {
        if (!Database::tableExists('notification_events_outbox')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM notification_events_outbox
            WHERE processed_at IS NULL
            ORDER BY created_at ASC, id ASC
            LIMIT :limit
        ');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function markProcessed(int $id): void
    {
        if (!Database::tableExists('notification_events_outbox')) {
            return;
        }

        $statement = $this->db->prepare('
            UPDATE notification_events_outbox
            SET processed_at = NOW()
            WHERE id = :id
        ');
        $statement->execute(['id' => $id]);
    }
}

