<?php

declare(strict_types=1);

final class Bid
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findForTasker(int $taskId, int $taskerId): ?array
    {
        $statement = $this->db->prepare('
            SELECT *
            FROM bids
            WHERE task_id = :task_id AND tasker_id = :tasker_id
            LIMIT 1
        ');
        $statement->execute([
            'task_id' => $taskId,
            'tasker_id' => $taskerId,
        ]);

        $bid = $statement->fetch();

        return $bid ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare('
            INSERT INTO bids (task_id, tasker_id, amount, message, status, created_at, updated_at)
            VALUES (:task_id, :tasker_id, :amount, :message, :status, NOW(), NOW())
        ');
        $statement->execute([
            'task_id' => $data['task_id'],
            'tasker_id' => $data['tasker_id'],
            'amount' => $data['amount'],
            'message' => $data['message'] !== '' ? $data['message'] : null,
            'status' => 'pending',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function forClientTask(int $taskId, int $clientId): array
    {
        $statement = $this->db->prepare('
            SELECT
                b.*,
                p.full_name AS tasker_name,
                u.email AS tasker_email
            FROM bids b
            INNER JOIN tasks t ON t.id = b.task_id
            INNER JOIN users u ON u.id = b.tasker_id
            INNER JOIN profiles p ON p.user_id = u.id
            WHERE b.task_id = :task_id AND t.client_id = :client_id
            ORDER BY
                CASE b.status
                    WHEN "accepted" THEN 0
                    WHEN "pending" THEN 1
                    WHEN "rejected" THEN 2
                    WHEN "withdrawn" THEN 3
                    ELSE 4
                END,
                b.created_at ASC
        ');
        $statement->execute([
            'task_id' => $taskId,
            'client_id' => $clientId,
        ]);

        return $statement->fetchAll();
    }

    public function findAcceptableForClient(int $bidId, int $clientId): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                b.*,
                t.client_id,
                t.status AS task_status,
                t.is_active AS task_is_active
            FROM bids b
            INNER JOIN tasks t ON t.id = b.task_id
            WHERE b.id = :bid_id AND t.client_id = :client_id
            LIMIT 1
        ');
        $statement->execute([
            'bid_id' => $bidId,
            'client_id' => $clientId,
        ]);

        $bid = $statement->fetch();

        return $bid ?: null;
    }

    public function markOtherBidsRejected(int $taskId, int $acceptedBidId): void
    {
        $statement = $this->db->prepare('
            UPDATE bids
            SET status = :rejected_status, updated_at = NOW()
            WHERE task_id = :task_id AND id != :accepted_bid_id AND status = :pending_status
        ');
        $statement->execute([
            'rejected_status' => 'rejected',
            'task_id' => $taskId,
            'accepted_bid_id' => $acceptedBidId,
            'pending_status' => 'pending',
        ]);
    }

    public function markAccepted(int $bidId): void
    {
        $statement = $this->db->prepare('
            UPDATE bids
            SET status = :accepted_status, updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'accepted_status' => 'accepted',
            'id' => $bidId,
        ]);
    }

    public function findByTaskerId(int $taskerId, array $statuses = []): array
    {
        $sql = '
            SELECT
                b.*,
                bk.id AS booking_id,
                t.title AS task_title,
                t.city,
                t.country,
                t.budget,
                t.scheduled_for,
                p.full_name AS client_name
            FROM bids b
            INNER JOIN tasks t ON t.id = b.task_id
            INNER JOIN profiles p ON p.user_id = t.client_id
            LEFT JOIN bookings bk ON bk.bid_id = b.id
            WHERE b.tasker_id = :tasker_id
        ';

        $params = ['tasker_id' => $taskerId];

        if ($statuses !== []) {
            $placeholders = [];

            foreach (array_values($statuses) as $index => $status) {
                $placeholder = 'status_' . $index;
                $placeholders[] = ':' . $placeholder;
                $params[$placeholder] = $status;
            }

            $sql .= ' AND b.status IN (' . implode(', ', $placeholders) . ')';
        }

        $sql .= ' ORDER BY b.created_at DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function countByTaskerId(int $taskerId): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM bids
            WHERE tasker_id = :tasker_id
        ');
        $statement->execute(['tasker_id' => $taskerId]);

        return (int) $statement->fetch()['count'];
    }

    public function countPendingForClient(int $clientId): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM bids b
            INNER JOIN tasks t ON t.id = b.task_id
            WHERE t.client_id = :client_id
              AND b.status = :status
              AND t.status = :task_status
              AND t.is_active = 1
        ');
        $statement->execute([
            'client_id' => $clientId,
            'status' => 'pending',
            'task_status' => 'open',
        ]);

        return (int) $statement->fetch()['count'];
    }
}
