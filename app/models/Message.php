<?php

declare(strict_types=1);

final class Message
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forBooking(int $bookingId): array
    {
        $statement = $this->db->prepare('
            SELECT
                m.id,
                m.booking_id,
                m.sender_id,
                m.body,
                m.created_at,
                p.full_name AS sender_name
            FROM messages m
            INNER JOIN profiles p ON p.user_id = m.sender_id
            WHERE m.booking_id = :booking_id
            ORDER BY m.created_at ASC, m.id ASC
        ');
        $statement->execute(['booking_id' => $bookingId]);

        return $statement->fetchAll();
    }

    public function forBookingSince(int $bookingId, string $sinceTimestamp): array
    {
        $statement = $this->db->prepare('
            SELECT
                m.id,
                m.booking_id,
                m.sender_id,
                m.body,
                m.created_at,
                p.full_name AS sender_name
            FROM messages m
            INNER JOIN profiles p ON p.user_id = m.sender_id
            WHERE m.booking_id = :booking_id AND m.created_at > :since_timestamp
            ORDER BY m.created_at ASC, m.id ASC
        ');
        $statement->execute([
            'booking_id' => $bookingId,
            'since_timestamp' => $sinceTimestamp,
        ]);

        return $statement->fetchAll();
    }

    public function forBookingAfterId(int $bookingId, int $afterId): array
    {
        $statement = $this->db->prepare('
            SELECT
                m.id,
                m.booking_id,
                m.sender_id,
                m.body,
                m.created_at,
                p.full_name AS sender_name
            FROM messages m
            INNER JOIN profiles p ON p.user_id = m.sender_id
            WHERE m.booking_id = :booking_id AND m.id > :after_id
            ORDER BY m.created_at ASC, m.id ASC
        ');
        $statement->execute([
            'booking_id' => $bookingId,
            'after_id' => $afterId,
        ]);

        return $statement->fetchAll();
    }

    public function countUnreadForUser(int $userId, string $role): int
    {
        // For simplicity, count messages from other participants in active bookings
        // In a real app, you'd track read status per message/thread
        $sql = '
            SELECT COUNT(*) as count
            FROM messages m
            INNER JOIN bookings b ON b.id = m.booking_id
            WHERE b.status = :status
              AND m.sender_id != :viewer_id
        ';

        $params = [
            'status' => 'active',
            'viewer_id' => $userId,
        ];

        if ($role === 'client') {
            $sql .= ' AND b.client_id = :participant_id';
            $params['participant_id'] = $userId;
        } elseif ($role === 'tasker') {
            $sql .= ' AND b.tasker_id = :participant_id';
            $params['participant_id'] = $userId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetch()['count'];
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare('
            INSERT INTO messages (booking_id, sender_id, body, created_at)
            VALUES (:booking_id, :sender_id, :body, NOW())
        ');
        $statement->execute([
            'booking_id' => $data['booking_id'],
            'sender_id' => $data['sender_id'],
            'body' => $data['body'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getRecentForUser(int $userId, string $role, int $limit = 5): array
    {
        $sql = '
            SELECT
                m.id,
                m.booking_id,
                m.sender_id,
                m.body,
                m.created_at,
                p.full_name AS sender_name,
                b.id AS booking_id,
                t.title AS task_title
            FROM messages m
            INNER JOIN profiles p ON p.user_id = m.sender_id
            INNER JOIN bookings b ON b.id = m.booking_id
            INNER JOIN tasks t ON t.id = b.task_id
            WHERE b.status = :status
              AND m.sender_id != :viewer_id
        ';

        $params = [
            'status' => 'active',
            'viewer_id' => $userId,
        ];

        if ($role === 'client') {
            $sql .= ' AND b.client_id = :participant_id';
            $params['participant_id'] = $userId;
        } elseif ($role === 'tasker') {
            $sql .= ' AND b.tasker_id = :participant_id';
            $params['participant_id'] = $userId;
        }

        $sql .= ' ORDER BY m.created_at DESC LIMIT :limit';

        $statement = $this->db->prepare($sql);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();

        return $statement->fetchAll();
    }
}
