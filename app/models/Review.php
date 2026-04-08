<?php

declare(strict_types=1);

final class Review
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByBookingAndReviewer(int $bookingId, int $reviewerId): ?array
    {
        $statement = $this->db->prepare('
            SELECT *
            FROM reviews
            WHERE booking_id = :booking_id AND reviewer_id = :reviewer_id
            LIMIT 1
        ');
        $statement->execute([
            'booking_id' => $bookingId,
            'reviewer_id' => $reviewerId,
        ]);

        $review = $statement->fetch();

        return $review ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare('
            INSERT INTO reviews (booking_id, reviewer_id, reviewee_id, rating, comment, created_at)
            VALUES (:booking_id, :reviewer_id, :reviewee_id, :rating, :comment, NOW())
        ');
        $statement->execute([
            'booking_id' => $data['booking_id'],
            'reviewer_id' => $data['reviewer_id'],
            'reviewee_id' => $data['reviewee_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] !== '' ? $data['comment'] : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function forBooking(int $bookingId): array
    {
        $statement = $this->db->prepare('
            SELECT
                r.*,
                p_reviewer.full_name AS reviewer_name,
                p_reviewee.full_name AS reviewee_name
            FROM reviews r
            INNER JOIN profiles p_reviewer ON p_reviewer.user_id = r.reviewer_id
            INNER JOIN profiles p_reviewee ON p_reviewee.user_id = r.reviewee_id
            WHERE r.booking_id = :booking_id
            ORDER BY r.created_at ASC
        ');
        $statement->execute(['booking_id' => $bookingId]);

        return $statement->fetchAll();
    }

    public function listByTaskerId(int $taskerId, string $sort = 'newest'): array
    {
        $order = match ($sort) {
            'highest' => 'r.rating DESC, r.created_at DESC',
            'lowest' => 'r.rating ASC, r.created_at DESC',
            default => 'r.created_at DESC',
        };

        $statement = $this->db->prepare('
            SELECT
                r.*,
                p_reviewer.full_name AS reviewer_name,
                t.title AS task_title
            FROM reviews r
            INNER JOIN bookings b ON b.id = r.booking_id
            INNER JOIN tasks t ON t.id = b.task_id
            LEFT JOIN profiles p_reviewer ON p_reviewer.user_id = r.reviewer_id
            WHERE r.reviewee_id = :tasker_id
            ORDER BY ' . $order . '
        ');
        $statement->execute(['tasker_id' => $taskerId]);

        return $statement->fetchAll();
    }

    public function getAggregatesByTaskerId(int $taskerId): array
    {
        $statement = $this->db->prepare('
            SELECT
                COUNT(*) AS review_count,
                COALESCE(AVG(rating), 0) AS average_rating
            FROM reviews
            WHERE reviewee_id = :tasker_id
        ');
        $statement->execute(['tasker_id' => $taskerId]);

        return $statement->fetch() ?: ['review_count' => 0, 'average_rating' => 0.0];
    }

    public function getAverageRatingByTaskerId(int $taskerId): float
    {
        $statement = $this->db->prepare('
            SELECT COALESCE(AVG(rating), 0) AS average_rating
            FROM reviews
            WHERE reviewee_id = :tasker_id
        ');
        $statement->execute(['tasker_id' => $taskerId]);

        return (float) $statement->fetch()['average_rating'];
    }

    public function countByTaskerId(int $taskerId): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM reviews
            WHERE reviewee_id = :tasker_id
        ');
        $statement->execute(['tasker_id' => $taskerId]);

        return (int) $statement->fetch()['count'];
    }
}
