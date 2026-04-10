<?php

declare(strict_types=1);

final class Message
{
    private PDO $db;
    private const LAST_SEEN_SESSION_KEY = '_message_last_seen_by_booking';

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
        $inbox = $this->getInboxForUser($userId, $role, 12);

        return array_sum(array_map(
            static fn (array $conversation): int => (int) ($conversation['unread_count'] ?? 0),
            $inbox
        ));
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

    public function getInboxForUser(int $userId, string $role, int $limit = 8): array
    {
        $sql = '
            SELECT
                b.id AS booking_id,
                t.title AS task_title,
                latest_message.id AS message_id,
                latest_message.sender_id,
                latest_message.body,
                latest_message.created_at,
                counterpart.full_name AS counterpart_name
            FROM bookings b
            INNER JOIN tasks t ON t.id = b.task_id
            INNER JOIN (
                SELECT booking_id, MAX(id) AS latest_message_id
                FROM messages
                GROUP BY booking_id
            ) latest ON latest.booking_id = b.id
            INNER JOIN messages latest_message ON latest_message.id = latest.latest_message_id
        ';

        $params = [
            'status' => 'active',
        ];

        if ($role === 'client') {
            $sql .= '
            INNER JOIN profiles counterpart ON counterpart.user_id = b.tasker_id
            WHERE b.status = :status
              AND b.client_id = :participant_id';
            $params['participant_id'] = $userId;
        } elseif ($role === 'tasker') {
            $sql .= '
            INNER JOIN profiles counterpart ON counterpart.user_id = b.client_id
            WHERE b.status = :status
              AND b.tasker_id = :participant_id';
            $params['participant_id'] = $userId;
        } else {
            return [];
        }

        $sql .= ' ORDER BY latest_message.created_at DESC LIMIT :limit';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->execute();

        $conversations = $statement->fetchAll();

        $unreadCounts = $this->unreadCountsForBookings(
            array_map(static fn (array $conversation): int => (int) ($conversation['booking_id'] ?? 0), $conversations),
            $userId
        );

        foreach ($conversations as &$conversation) {
            $conversation['unread_count'] = (int) ($unreadCounts[(int) $conversation['booking_id']] ?? 0);
        }
        unset($conversation);

        return $conversations;
    }

    public function markThreadSeen(int $bookingId, int $messageId): void
    {
        if ($bookingId <= 0 || $messageId <= 0) {
            return;
        }

        $lastSeenByBooking = Session::get(self::LAST_SEEN_SESSION_KEY, []);

        if (!is_array($lastSeenByBooking)) {
            $lastSeenByBooking = [];
        }

        if ($messageId > (int) ($lastSeenByBooking[$bookingId] ?? 0)) {
            $lastSeenByBooking[$bookingId] = $messageId;
            Session::put(self::LAST_SEEN_SESSION_KEY, $lastSeenByBooking);
        }
    }

    private function countUnreadForConversation(int $bookingId, int $userId): int
    {
        $lastSeenByBooking = Session::get(self::LAST_SEEN_SESSION_KEY, []);
        $lastSeenId = is_array($lastSeenByBooking) ? (int) ($lastSeenByBooking[$bookingId] ?? 0) : 0;

        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM messages
            WHERE booking_id = :booking_id
              AND sender_id != :viewer_id
              AND id > :last_seen_id
        ');
        $statement->execute([
            'booking_id' => $bookingId,
            'viewer_id' => $userId,
            'last_seen_id' => $lastSeenId,
        ]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    private function unreadCountsForBookings(array $bookingIds, int $userId): array
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $bookingId): int => (int) $bookingId, $bookingIds),
            static fn (int $bookingId): bool => $bookingId > 0
        )));

        if ($normalizedIds === []) {
            return [];
        }

        $lastSeenByBooking = Session::get(self::LAST_SEEN_SESSION_KEY, []);
        $conditions = [];
        $params = ['viewer_id' => $userId];

        foreach ($normalizedIds as $index => $bookingId) {
            $bookingPlaceholder = 'booking_id_' . $index;
            $lastSeenPlaceholder = 'last_seen_' . $index;
            $conditions[] = '(booking_id = :' . $bookingPlaceholder . ' AND id > :' . $lastSeenPlaceholder . ')';
            $params[$bookingPlaceholder] = $bookingId;
            $params[$lastSeenPlaceholder] = is_array($lastSeenByBooking) ? (int) ($lastSeenByBooking[$bookingId] ?? 0) : 0;
        }

        $statement = $this->db->prepare('
            SELECT booking_id, COUNT(*) AS aggregate
            FROM messages
            WHERE sender_id != :viewer_id
              AND (' . implode(' OR ', $conditions) . ')
            GROUP BY booking_id
        ');
        $statement->execute($params);

        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[(int) $row['booking_id']] = (int) $row['aggregate'];
        }

        return $counts;
    }
}
