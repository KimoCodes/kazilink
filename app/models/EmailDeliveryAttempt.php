<?php

declare(strict_types=1);

final class EmailDeliveryAttempt
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): void
    {
        if (!Database::tableExists('email_delivery_attempts')) {
            return;
        }

        $statement = $this->db->prepare('
            INSERT INTO email_delivery_attempts (
                outbox_id,
                attempt_number,
                provider,
                request_payload_json,
                response_payload_json,
                result,
                error_code,
                error_message,
                created_at
            ) VALUES (
                :outbox_id,
                :attempt_number,
                :provider,
                :request_payload_json,
                :response_payload_json,
                :result,
                :error_code,
                :error_message,
                NOW()
            )
        ');
        $statement->execute([
            'outbox_id' => $data['outbox_id'],
            'attempt_number' => $data['attempt_number'],
            'provider' => $data['provider'],
            'request_payload_json' => $data['request_payload_json'] ?? null,
            'response_payload_json' => $data['response_payload_json'] ?? null,
            'result' => $data['result'],
            'error_code' => $data['error_code'] ?? null,
            'error_message' => $data['error_message'] ?? null,
        ]);
    }

    public function latestForOutboxIds(array $outboxIds): array
    {
        if ($outboxIds === [] || !Database::tableExists('email_delivery_attempts')) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($outboxIds), '?'));
        $statement = $this->db->prepare("
            SELECT a.*
            FROM email_delivery_attempts a
            INNER JOIN (
                SELECT outbox_id, MAX(id) AS latest_id
                FROM email_delivery_attempts
                WHERE outbox_id IN ($placeholders)
                GROUP BY outbox_id
            ) latest ON latest.latest_id = a.id
        ");
        $statement->execute(array_map('intval', $outboxIds));

        $records = [];
        foreach ($statement->fetchAll() as $row) {
            $records[(int) $row['outbox_id']] = $row;
        }

        return $records;
    }
}
