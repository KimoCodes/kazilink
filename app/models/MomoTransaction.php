<?php

declare(strict_types=1);

final class MomoTransaction
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        if (!Database::tableExists('momo_transactions')) {
            throw new RuntimeException('The momo_transactions table does not exist yet. Run the subscription migration first.');
        }

        $statement = $this->db->prepare('
            INSERT INTO momo_transactions (user_id, purpose, amount_rwf, external_ref, status, raw_payload_json, created_at, updated_at)
            VALUES (:user_id, :purpose, :amount_rwf, :external_ref, :status, :raw_payload_json, NOW(), NOW())
        ');
        $statement->execute([
            'user_id' => $data['user_id'],
            'purpose' => 'subscription',
            'amount_rwf' => $data['amount_rwf'],
            'external_ref' => $data['external_ref'],
            'status' => $data['status'] ?? 'pending',
            'raw_payload_json' => $data['raw_payload_json'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByExternalRef(string $externalRef): ?array
    {
        if (!Database::tableExists('momo_transactions')) {
            return null;
        }

        $statement = $this->db->prepare('SELECT * FROM momo_transactions WHERE external_ref = :external_ref LIMIT 1');
        $statement->execute(['external_ref' => $externalRef]);
        $transaction = $statement->fetch();

        return $transaction ?: null;
    }

    public function findById(int $id): ?array
    {
        if (!Database::tableExists('momo_transactions')) {
            return null;
        }

        $statement = $this->db->prepare('SELECT * FROM momo_transactions WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $transaction = $statement->fetch();

        return $transaction ?: null;
    }

    public function updateGatewayPayload(string $externalRef, array $payload, ?string $status = null): void
    {
        if (!Database::tableExists('momo_transactions')) {
            return;
        }

        $existing = $this->findByExternalRef($externalRef);
        if ($existing === null) {
            return;
        }

        $existingPayload = json_decode((string) ($existing['raw_payload_json'] ?? '{}'), true);
        $existingPayload = is_array($existingPayload) ? $existingPayload : [];
        $history = is_array($existingPayload['history'] ?? null) ? $existingPayload['history'] : [];
        $history[] = [
            'at' => date(DATE_ATOM),
            'payload' => $payload,
        ];

        $mergedPayload = array_merge($existingPayload, $payload, [
            'history' => $history,
        ]);

        $resolvedStatus = $status;
        if ((string) ($existing['status'] ?? '') === 'successful' && $resolvedStatus !== 'successful') {
            $resolvedStatus = null;
        }

        $sql = '
            UPDATE momo_transactions
            SET raw_payload_json = :raw_payload_json,
                updated_at = NOW()
        ';
        $params = [
            'raw_payload_json' => json_encode($mergedPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'external_ref' => $externalRef,
        ];

        if ($resolvedStatus !== null) {
            $sql .= ', status = :status';
            $params['status'] = $resolvedStatus;
        }

        $sql .= ' WHERE external_ref = :external_ref';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    public function latestForUser(int $userId, int $limit = 20): array
    {
        if (!Database::tableExists('momo_transactions')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM momo_transactions
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ');
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function latestForAdmin(int $limit = 100, int $offset = 0): array
    {
        if (!Database::tableExists('momo_transactions')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT
                mt.*,
                p.full_name,
                u.email
            FROM momo_transactions mt
            INNER JOIN users u ON u.id = mt.user_id
            INNER JOIN profiles p ON p.user_id = u.id
            ORDER BY mt.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countForAdmin(): int
    {
        if (!Database::tableExists('momo_transactions')) {
            return 0;
        }

        $statement = $this->db->prepare('SELECT COUNT(*) AS aggregate FROM momo_transactions');
        $statement->execute();

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }
}
