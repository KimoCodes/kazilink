<?php

declare(strict_types=1);

final class Booking
{
    private PDO $db;
    private HiringAgreement $agreements;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->agreements = new HiringAgreement($this->db);
    }

    public function createFromBid(array $bid): int
    {
        $this->db->beginTransaction();

        try {
            $lockTask = $this->db->prepare('SELECT id, status, is_active FROM tasks WHERE id = :id FOR UPDATE');
            $lockTask->execute(['id' => $bid['task_id']]);
            $task = $lockTask->fetch();

            if (!$task || $task['status'] !== 'open' || (int) $task['is_active'] !== 1) {
                throw new RuntimeException('That task is no longer available for booking.');
            }

            $lockExistingBooking = $this->db->prepare('
                SELECT id
                FROM bookings
                WHERE task_id = :task_id
                LIMIT 1
                FOR UPDATE
            ');
            $lockExistingBooking->execute(['task_id' => $bid['task_id']]);

            if ($lockExistingBooking->fetch()) {
                throw new RuntimeException('This task already has a booking.');
            }

            $lockBid = $this->db->prepare('SELECT * FROM bids WHERE id = :id FOR UPDATE');
            $lockBid->execute(['id' => $bid['id']]);
            $lockedBid = $lockBid->fetch();

            if (!$lockedBid || $lockedBid['status'] !== 'pending') {
                throw new RuntimeException('That bid can no longer be accepted.');
            }

            $bookingStmt = $this->db->prepare('
                INSERT INTO bookings (task_id, bid_id, client_id, tasker_id, status, booked_at, created_at, updated_at)
                VALUES (:task_id, :bid_id, :client_id, :tasker_id, :status, NOW(), NOW(), NOW())
            ');
            $bookingStmt->execute([
                'task_id' => $lockedBid['task_id'],
                'bid_id' => $lockedBid['id'],
                'client_id' => $bid['client_id'],
                'tasker_id' => $lockedBid['tasker_id'],
                'status' => 'active',
            ]);

            $bookingId = (int) $this->db->lastInsertId();

            $acceptStmt = $this->db->prepare('
                UPDATE bids
                SET status = :accepted_status, updated_at = NOW()
                WHERE id = :id
            ');
            $acceptStmt->execute([
                'accepted_status' => 'accepted',
                'id' => $lockedBid['id'],
            ]);

            $rejectStmt = $this->db->prepare('
                UPDATE bids
                SET status = :rejected_status, updated_at = NOW()
                WHERE task_id = :task_id AND id != :accepted_bid_id AND status = :pending_status
            ');
            $rejectStmt->execute([
                'rejected_status' => 'rejected',
                'task_id' => $lockedBid['task_id'],
                'accepted_bid_id' => $lockedBid['id'],
                'pending_status' => 'pending',
            ]);

            $taskStmt = $this->db->prepare('
                UPDATE tasks
                SET status = :booked_status, updated_at = NOW()
                WHERE id = :id
            ');
            $taskStmt->execute([
                'booked_status' => 'booked',
                'id' => $lockedBid['task_id'],
            ]);

            $this->agreements->createDraftForBooking($bookingId);

            $this->db->commit();

            return $bookingId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function forUser(int $userId, string $role, int $limit = 25, int $offset = 0): array
    {
        $sql = '
            SELECT
                b.id,
                b.task_id,
                b.status,
                b.booked_at,
                t.title,
                t.city,
                t.country,
                t.budget,
                bid.amount AS agreed_amount,
                b.tasker_id,
                p_client.full_name AS client_name,
                p_tasker.full_name AS tasker_name
            FROM bookings b
            INNER JOIN tasks t ON t.id = b.task_id
            INNER JOIN bids bid ON bid.id = b.bid_id
            INNER JOIN profiles p_client ON p_client.user_id = b.client_id
            INNER JOIN profiles p_tasker ON p_tasker.user_id = b.tasker_id
        ';

        $params = [];

        if ($role === 'client') {
            $sql .= ' WHERE b.client_id = :user_id';
            $params['user_id'] = $userId;
        } elseif ($role === 'tasker') {
            $sql .= ' WHERE b.tasker_id = :user_id';
            $params['user_id'] = $userId;
        }

        $sql .= ' ORDER BY b.booked_at DESC, b.id DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function countForUser(int $userId, string $role): int
    {
        $sql = 'SELECT COUNT(*) AS aggregate FROM bookings b';
        $params = [];

        if ($role === 'client') {
            $sql .= ' WHERE b.client_id = :user_id';
            $params['user_id'] = $userId;
        } elseif ($role === 'tasker') {
            $sql .= ' WHERE b.tasker_id = :user_id';
            $params['user_id'] = $userId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function findVisibleById(int $bookingId, int $userId, string $role): ?array
    {
        if ($role === 'admin') {
            $statement = $this->db->prepare('
                SELECT
                    b.*,
                    b.tasker_id,
                    bid.amount AS agreed_amount,
                    t.title,
                    t.description,
                    t.scheduled_for,
                    t.city,
                    t.region,
                    t.country,
                    t.budget,
                    p_client.full_name AS client_name,
                    p_tasker.full_name AS tasker_name
                FROM bookings b
                INNER JOIN tasks t ON t.id = b.task_id
                INNER JOIN bids bid ON bid.id = b.bid_id
                INNER JOIN profiles p_client ON p_client.user_id = b.client_id
                INNER JOIN profiles p_tasker ON p_tasker.user_id = b.tasker_id
                WHERE b.id = :id
                LIMIT 1
            ');
            $statement->execute(['id' => $bookingId]);
        } else {
            $statement = $this->db->prepare('
                SELECT
                    b.*,
                    b.tasker_id,
                    bid.amount AS agreed_amount,
                    t.title,
                    t.description,
                    t.scheduled_for,
                    t.city,
                    t.region,
                    t.country,
                    t.budget,
                    p_client.full_name AS client_name,
                    p_tasker.full_name AS tasker_name
                FROM bookings b
                INNER JOIN tasks t ON t.id = b.task_id
                INNER JOIN bids bid ON bid.id = b.bid_id
                INNER JOIN profiles p_client ON p_client.user_id = b.client_id
                INNER JOIN profiles p_tasker ON p_tasker.user_id = b.tasker_id
                WHERE b.id = :id AND (b.client_id = :client_user_id OR b.tasker_id = :tasker_user_id)
                LIMIT 1
            ');
            $statement->execute([
                'id' => $bookingId,
                'client_user_id' => $userId,
                'tasker_user_id' => $userId,
            ]);
        }

        $booking = $statement->fetch();

        return $booking ?: null;
    }

    public function completeForClient(int $bookingId, int $clientId): void
    {
        $this->db->beginTransaction();

        try {
            $bookingStmt = $this->db->prepare('
                SELECT id, task_id, status
                FROM bookings
                WHERE id = :id AND client_id = :client_id
                FOR UPDATE
            ');
            $bookingStmt->execute([
                'id' => $bookingId,
                'client_id' => $clientId,
            ]);
            $booking = $bookingStmt->fetch();

            if (!$booking) {
                throw new RuntimeException('Booking not found.');
            }

            if ($booking['status'] !== 'active') {
                throw new RuntimeException('Only active bookings can be completed.');
            }

            $agreementStmt = $this->db->prepare('
                SELECT id, status
                FROM hiring_agreements
                WHERE booking_id = :booking_id
                LIMIT 1
                FOR UPDATE
            ');
            $agreementStmt->execute(['booking_id' => $bookingId]);
            $agreement = $agreementStmt->fetch();

            if (!$agreement) {
                throw new RuntimeException('The booking agreement record is missing.');
            }

            if ((string) $agreement['status'] !== 'accepted') {
                throw new RuntimeException('Booking cannot be completed until the agreement is fully accepted.');
            }

            $updateBooking = $this->db->prepare('
                UPDATE bookings
                SET status = :completed_status, completed_at = NOW(), updated_at = NOW()
                WHERE id = :id
            ');
            $updateBooking->execute([
                'completed_status' => 'completed',
                'id' => $bookingId,
            ]);

            $updateTask = $this->db->prepare('
                UPDATE tasks
                SET status = :completed_status, updated_at = NOW()
                WHERE id = :task_id
            ');
            $updateTask->execute([
                'completed_status' => 'completed',
                'task_id' => $booking['task_id'],
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function canClientReview(int $bookingId, int $clientId): bool
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM bookings
            WHERE id = :id
              AND client_id = :client_id
              AND status = :status
        ');
        $statement->execute([
            'id' => $bookingId,
            'client_id' => $clientId,
            'status' => 'completed',
        ]);

        return (int) ($statement->fetch()['aggregate'] ?? 0) === 1;
    }

    public function countCompletedByTaskerId(int $taskerId): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM bookings
            WHERE tasker_id = :tasker_id
              AND status = :status
        ');
        $statement->execute([
            'tasker_id' => $taskerId,
            'status' => 'completed',
        ]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function getStatsByTaskerId(int $taskerId): array
    {
        $statement = $this->db->prepare('
            SELECT
                COUNT(*) AS total_jobs,
                SUM(status = :completed_status) AS completed_jobs,
                MAX(completed_at) AS last_completed_at
            FROM bookings
            WHERE tasker_id = :tasker_id
        ');
        $statement->execute([
            'tasker_id' => $taskerId,
            'completed_status' => 'completed',
        ]);

        $stats = $statement->fetch() ?: ['total_jobs' => 0, 'completed_jobs' => 0, 'last_completed_at' => null];
        $completedJobs = (int) ($stats['completed_jobs'] ?? 0);
        $totalJobs = (int) ($stats['total_jobs'] ?? 0);

        return [
            'completed_jobs' => $completedJobs,
            'total_jobs' => $totalJobs,
            'completion_rate' => $totalJobs > 0 ? sprintf('%.0f%%', ($completedJobs / $totalJobs) * 100) : '0%',
            'last_completed_at' => $stats['last_completed_at'] ?? null,
        ];
    }

    public function findByTaskId(int $taskId): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                b.id,
                b.tasker_id,
                b.status,
                p_tasker.full_name AS tasker_name
            FROM bookings b
            INNER JOIN profiles p_tasker ON p_tasker.user_id = b.tasker_id
            WHERE b.task_id = :task_id
            LIMIT 1
        ');
        $statement->execute(['task_id' => $taskId]);
        $booking = $statement->fetch();

        return $booking ?: null;
    }

    public function findByTaskerId(int $taskerId, int $limit = 0): array
    {
        $sql = '
            SELECT
                b.id,
                b.task_id,
                b.status,
                b.booked_at,
                b.completed_at,
                t.title,
                t.city,
                t.country,
                t.budget,
                bid.amount AS agreed_amount,
                p_client.full_name AS client_name
            FROM bookings b
            INNER JOIN tasks t ON t.id = b.task_id
            INNER JOIN bids bid ON bid.id = b.bid_id
            INNER JOIN profiles p_client ON p_client.user_id = b.client_id
            WHERE b.tasker_id = :tasker_id
            ORDER BY b.booked_at DESC
        ';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute(['tasker_id' => $taskerId]);

        return $statement->fetchAll();
    }

    public function countByTaskerId(int $taskerId): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM bookings
            WHERE tasker_id = :tasker_id
        ');
        $statement->execute(['tasker_id' => $taskerId]);

        return (int) $statement->fetch()['count'];
    }

    public function countByTaskerIdAndStatus(int $taskerId, string $status): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM bookings
            WHERE tasker_id = :tasker_id AND status = :status
        ');
        $statement->execute([
            'tasker_id' => $taskerId,
            'status' => $status,
        ]);

        return (int) $statement->fetch()['count'];
    }

    public function getTotalEarningsByTaskerId(int $taskerId): float
    {
        $statement = $this->db->prepare('
            SELECT COALESCE(SUM(t.budget), 0) as total_earnings
            FROM bookings b
            INNER JOIN tasks t ON t.id = b.task_id
            WHERE b.tasker_id = :tasker_id AND b.status = :status
        ');
        $statement->execute([
            'tasker_id' => $taskerId,
            'status' => 'completed',
        ]);

        return (float) $statement->fetch()['total_earnings'];
    }
}
