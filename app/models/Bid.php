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

    public function createForTaskerOnOpenTask(array $data, int $dailyLimit): int
    {
        if (!Database::tableExists('user_metrics')) {
            throw new RuntimeException('Application limits are unavailable right now. Please try again later.');
        }

        $this->db->beginTransaction();

        try {
            $taskStatement = $this->db->prepare('
                SELECT id, client_id, status, is_active
                FROM tasks
                WHERE id = :task_id
                FOR UPDATE
            ');
            $taskStatement->execute(['task_id' => $data['task_id']]);
            $task = $taskStatement->fetch();

            if (!$task || (string) $task['status'] !== 'open' || (int) $task['is_active'] !== 1) {
                throw new RuntimeException('That task is not available for bidding.');
            }

            if ((int) $task['client_id'] === (int) $data['tasker_id']) {
                throw new RuntimeException('You cannot bid on your own task.');
            }

            $existingStatement = $this->db->prepare('
                SELECT id
                FROM bids
                WHERE task_id = :task_id AND tasker_id = :tasker_id
                LIMIT 1
                FOR UPDATE
            ');
            $existingStatement->execute([
                'task_id' => $data['task_id'],
                'tasker_id' => $data['tasker_id'],
            ]);

            if ($existingStatement->fetch()) {
                throw new RuntimeException('You have already submitted a bid for this task.');
            }

            $seedMetrics = $this->db->prepare('
                INSERT INTO user_metrics (user_id, daily_applications_count, last_reset_date, created_at, updated_at)
                VALUES (:user_id, 0, :last_reset_date, NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at = updated_at
            ');
            $today = date('Y-m-d');
            $seedMetrics->execute([
                'user_id' => $data['tasker_id'],
                'last_reset_date' => $today,
            ]);

            $metricsStatement = $this->db->prepare('
                SELECT user_id, daily_applications_count, last_reset_date
                FROM user_metrics
                WHERE user_id = :user_id
                LIMIT 1
                FOR UPDATE
            ');
            $metricsStatement->execute(['user_id' => $data['tasker_id']]);
            $metrics = $metricsStatement->fetch();

            if (!$metrics) {
                throw new RuntimeException('Application limits are unavailable right now. Please try again later.');
            }

            $currentCount = (int) ($metrics['daily_applications_count'] ?? 0);
            $lastResetDate = (string) ($metrics['last_reset_date'] ?? '');

            if ($lastResetDate !== $today) {
                $resetStatement = $this->db->prepare('
                    UPDATE user_metrics
                    SET daily_applications_count = 0,
                        last_reset_date = :last_reset_date,
                        updated_at = NOW()
                    WHERE user_id = :user_id
                ');
                $resetStatement->execute([
                    'last_reset_date' => $today,
                    'user_id' => $data['tasker_id'],
                ]);
                $currentCount = 0;
            }

            if ($currentCount >= $dailyLimit) {
                throw new RuntimeException(sprintf(
                    'Your daily limit is used up. You can submit %d application%s per day.',
                    $dailyLimit,
                    $dailyLimit === 1 ? '' : 's'
                ));
            }

            $insertStatement = $this->db->prepare('
                INSERT INTO bids (task_id, tasker_id, amount, message, status, created_at, updated_at)
                VALUES (:task_id, :tasker_id, :amount, :message, :status, NOW(), NOW())
            ');
            $insertStatement->execute([
                'task_id' => $data['task_id'],
                'tasker_id' => $data['tasker_id'],
                'amount' => $data['amount'],
                'message' => $data['message'] !== '' ? $data['message'] : null,
                'status' => 'pending',
            ]);

            $incrementStatement = $this->db->prepare('
                UPDATE user_metrics
                SET daily_applications_count = daily_applications_count + 1,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ');
            $incrementStatement->execute(['user_id' => $data['tasker_id']]);

            $bidId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return $bidId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                throw new RuntimeException('You have already submitted a bid for this task.', 0, $exception);
            }

            throw $exception;
        }
    }

    public function forClientTask(int $taskId, int $clientId): array
    {
        $statement = $this->db->prepare('
            SELECT
                b.*,
                p.full_name AS tasker_name,
                u.email AS tasker_email,
                COALESCE(tasker_plan.priority_level, 1) AS tasker_priority_level,
                tasker_plan.badge_name AS tasker_badge_name
            FROM bids b
            INNER JOIN tasks t ON t.id = b.task_id
            INNER JOIN users u ON u.id = b.tasker_id
            INNER JOIN profiles p ON p.user_id = u.id
            LEFT JOIN (
                SELECT
                    s.user_id,
                    p2.priority_level,
                    p2.badge_name
                FROM subscriptions s
                INNER JOIN plans p2 ON p2.id = COALESCE(s.active_plan_id, s.plan_id)
                INNER JOIN (
                    SELECT user_id, MAX(id) AS max_id
                    FROM subscriptions
                    GROUP BY user_id
                ) latest ON latest.max_id = s.id
            ) AS tasker_plan ON tasker_plan.user_id = b.tasker_id
            WHERE b.task_id = :task_id AND t.client_id = :client_id
            ORDER BY
                CASE b.status
                    WHEN "accepted" THEN 0
                    WHEN "pending" THEN 1
                    WHEN "rejected" THEN 2
                    WHEN "withdrawn" THEN 3
                    ELSE 4
                END,
                tasker_priority_level DESC,
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
