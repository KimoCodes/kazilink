<?php

declare(strict_types=1);

final class EmailOutbox
{
    private PDO $db;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_RETRY_SCHEDULED = 'retry_scheduled';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED_TRANSIENT = 'failed_transient';
    public const STATUS_FAILED_PERMANENT = 'failed_permanent';
    public const STATUS_SKIPPED = 'skipped';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $message): array
    {
        if (!Database::tableExists('email_outbox')) {
            return ['created' => false, 'id' => null];
        }

        $statement = $this->db->prepare('
            INSERT IGNORE INTO email_outbox (
                event_name,
                entity_type,
                entity_id,
                recipient_email,
                recipient_name,
                template_name,
                subject,
                template_data_json,
                idempotency_key,
                status,
                attempt_count,
                next_attempt_at,
                provider_message_id,
                last_error_code,
                last_error_message,
                metadata_json,
                created_at,
                updated_at,
                processed_at,
                sent_at
            ) VALUES (
                :event_name,
                :entity_type,
                :entity_id,
                :recipient_email,
                :recipient_name,
                :template_name,
                :subject,
                :template_data_json,
                :idempotency_key,
                :status,
                :attempt_count,
                :next_attempt_at,
                :provider_message_id,
                :last_error_code,
                :last_error_message,
                :metadata_json,
                NOW(),
                NOW(),
                :processed_at,
                :sent_at
            )
        ');
        $statement->execute([
            'event_name' => $message['event_name'],
            'entity_type' => $message['entity_type'],
            'entity_id' => (int) $message['entity_id'],
            'recipient_email' => mb_strtolower(trim((string) $message['recipient_email'])),
            'recipient_name' => $message['recipient_name'] ?? null,
            'template_name' => $message['template_name'],
            'subject' => $message['subject'],
            'template_data_json' => $message['template_data_json'],
            'idempotency_key' => $message['idempotency_key'],
            'status' => $message['status'],
            'attempt_count' => (int) ($message['attempt_count'] ?? 0),
            'next_attempt_at' => $message['next_attempt_at'] ?? date('Y-m-d H:i:s'),
            'provider_message_id' => $message['provider_message_id'] ?? null,
            'last_error_code' => $message['last_error_code'] ?? null,
            'last_error_message' => $message['last_error_message'] ?? null,
            'metadata_json' => $message['metadata_json'] ?? null,
            'processed_at' => $message['processed_at'] ?? null,
            'sent_at' => $message['sent_at'] ?? null,
        ]);

        if ($statement->rowCount() > 0) {
            return ['created' => true, 'id' => (int) $this->db->lastInsertId()];
        }

        return ['created' => false, 'id' => $this->findIdByIdempotencyKey((string) $message['idempotency_key'])];
    }

    public function claimDue(int $limit = 50): array
    {
        if (!Database::tableExists('email_outbox')) {
            return [];
        }

        $select = $this->db->prepare('
            SELECT id
            FROM email_outbox
            WHERE status IN (:pending, :retry_scheduled)
              AND next_attempt_at <= NOW()
            ORDER BY next_attempt_at ASC, id ASC
            LIMIT :limit
        ');
        $select->bindValue(':pending', self::STATUS_PENDING);
        $select->bindValue(':retry_scheduled', self::STATUS_RETRY_SCHEDULED);
        $select->bindValue(':limit', $limit, PDO::PARAM_INT);
        $select->execute();

        $claimed = [];
        foreach ($select->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $update = $this->db->prepare('
                UPDATE email_outbox
                SET status = :processing,
                    updated_at = NOW()
                WHERE id = :id
                  AND status IN (:pending, :retry_scheduled)
                  AND next_attempt_at <= NOW()
            ');
            $update->execute([
                'processing' => self::STATUS_PROCESSING,
                'id' => (int) $id,
                'pending' => self::STATUS_PENDING,
                'retry_scheduled' => self::STATUS_RETRY_SCHEDULED,
            ]);

            if ($update->rowCount() > 0) {
                $record = $this->findById((int) $id);
                if ($record !== null) {
                    $claimed[] = $record;
                }
            }
        }

        return $claimed;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM email_outbox WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $record = $statement->fetch();

        return is_array($record) ? $record : null;
    }

    public function markSent(int $id, int $attemptCount, ?string $providerMessageId): void
    {
        $statement = $this->db->prepare('
            UPDATE email_outbox
            SET status = :status,
                attempt_count = :attempt_count,
                provider_message_id = :provider_message_id,
                last_error_code = NULL,
                last_error_message = NULL,
                updated_at = NOW(),
                processed_at = NOW(),
                sent_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'status' => self::STATUS_SENT,
            'attempt_count' => $attemptCount,
            'provider_message_id' => $providerMessageId,
            'id' => $id,
        ]);
    }

    public function scheduleRetry(int $id, int $attemptCount, string $errorCode, string $errorMessage, string $nextAttemptAt): void
    {
        $statement = $this->db->prepare('
            UPDATE email_outbox
            SET status = :status,
                attempt_count = :attempt_count,
                last_error_code = :error_code,
                last_error_message = :error_message,
                next_attempt_at = :next_attempt_at,
                updated_at = NOW(),
                processed_at = NULL
            WHERE id = :id
        ');
        $statement->execute([
            'status' => self::STATUS_RETRY_SCHEDULED,
            'attempt_count' => $attemptCount,
            'error_code' => mb_substr($errorCode, 0, 120),
            'error_message' => mb_substr($errorMessage, 0, 255),
            'next_attempt_at' => $nextAttemptAt,
            'id' => $id,
        ]);
    }

    public function markFailedPermanent(int $id, int $attemptCount, string $errorCode, string $errorMessage): void
    {
        $statement = $this->db->prepare('
            UPDATE email_outbox
            SET status = :status,
                attempt_count = :attempt_count,
                last_error_code = :error_code,
                last_error_message = :error_message,
                updated_at = NOW(),
                processed_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'status' => self::STATUS_FAILED_PERMANENT,
            'attempt_count' => $attemptCount,
            'error_code' => mb_substr($errorCode, 0, 120),
            'error_message' => mb_substr($errorMessage, 0, 255),
            'id' => $id,
        ]);
    }

    public function markSkipped(int $id, string $reason): void
    {
        $statement = $this->db->prepare('
            UPDATE email_outbox
            SET status = :status,
                last_error_code = :error_code,
                last_error_message = :error_message,
                updated_at = NOW(),
                processed_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'status' => self::STATUS_SKIPPED,
            'error_code' => 'recipient_suppressed',
            'error_message' => mb_substr($reason, 0, 255),
            'id' => $id,
        ]);
    }

    public function forAdmin(array $filters = [], int $limit = 100): array
    {
        $conditions = [];
        $params = [];

        if (($filters['status'] ?? '') !== '') {
            $conditions[] = 'e.status = :status';
            $params['status'] = $filters['status'];
        }

        if (($filters['event_name'] ?? '') !== '') {
            $conditions[] = 'e.event_name = :event_name';
            $params['event_name'] = $filters['event_name'];
        }

        if (($filters['recipient_email'] ?? '') !== '') {
            $conditions[] = 'e.recipient_email LIKE :recipient_email';
            $params['recipient_email'] = '%' . $filters['recipient_email'] . '%';
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $statement = $this->db->prepare("
            SELECT e.*
            FROM email_outbox e
            $where
            ORDER BY e.created_at DESC, e.id DESC
            LIMIT :limit
        ");

        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function metrics(): array
    {
        if (!Database::tableExists('email_outbox')) {
            return [];
        }

        $rows = $this->db->query('
            SELECT status, COUNT(*) AS aggregate
            FROM email_outbox
            GROUP BY status
        ')->fetchAll();
        $metrics = [];
        foreach ($rows as $row) {
            $metrics[(string) $row['status']] = (int) $row['aggregate'];
        }

        return $metrics;
    }

    public function createResend(int $id, int $adminUserId): ?int
    {
        $original = $this->findById($id);
        if ($original === null) {
            return null;
        }

        $newKey = (string) $original['idempotency_key'] . ':resend:' . $adminUserId . ':' . time();
        $created = $this->create([
            'event_name' => (string) $original['event_name'] . '_resend',
            'entity_type' => (string) $original['entity_type'],
            'entity_id' => (int) $original['entity_id'],
            'recipient_email' => (string) $original['recipient_email'],
            'recipient_name' => (string) ($original['recipient_name'] ?? ''),
            'template_name' => (string) $original['template_name'],
            'subject' => (string) $original['subject'],
            'template_data_json' => (string) $original['template_data_json'],
            'idempotency_key' => $newKey,
            'status' => self::STATUS_PENDING,
            'metadata_json' => json_encode([
                'resend_of_outbox_id' => $id,
                'requested_by_admin_id' => $adminUserId,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return $created['id'];
    }

    private function findIdByIdempotencyKey(string $key): ?int
    {
        $statement = $this->db->prepare('SELECT id FROM email_outbox WHERE idempotency_key = :key LIMIT 1');
        $statement->execute(['key' => $key]);
        $value = $statement->fetchColumn();

        return $value !== false ? (int) $value : null;
    }
}
