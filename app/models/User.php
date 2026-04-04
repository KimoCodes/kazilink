<?php

declare(strict_types=1);

final class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByEmail(string $email): ?array
    {
        $sql = '
            SELECT u.*, p.full_name
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.email = :email
            LIMIT 1
        ';

        $statement = $this->db->prepare($sql);
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('
            SELECT u.*, p.full_name
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function createWithProfile(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $userStmt = $this->db->prepare('
                INSERT INTO users (email, password_hash, role, is_active, failed_login_attempts, last_failed_login_at, last_login_at, created_at, updated_at)
                VALUES (:email, :password_hash, :role, 1, 0, NULL, NULL, NOW(), NOW())
            ');

            $userStmt->execute([
                'email' => $data['email'],
                'password_hash' => $data['password_hash'],
                'role' => $data['role'],
            ]);

            $userId = (int) $this->db->lastInsertId();

            $profileStmt = $this->db->prepare('
                INSERT INTO profiles (user_id, full_name, phone, city, region, country, bio, avatar_path, skills_summary, created_at, updated_at)
                VALUES (:user_id, :full_name, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NOW())
            ');

            $profileStmt->execute([
                'user_id' => $userId,
                'full_name' => $data['full_name'],
            ]);

            $this->db->commit();

            return $userId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function isLockedOut(array $user): bool
    {
        $attempts = (int) ($user['failed_login_attempts'] ?? 0);
        $lastFailed = $user['last_failed_login_at'] ?? null;

        if ($attempts < 5 || !$lastFailed) {
            return false;
        }

        $lastFailedTime = strtotime((string) $lastFailed);

        return $lastFailedTime !== false && $lastFailedTime > strtotime('-15 minutes');
    }

    public function recordFailedLogin(int $userId): void
    {
        $statement = $this->db->prepare('
            UPDATE users
            SET failed_login_attempts = failed_login_attempts + 1,
                last_failed_login_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ');

        $statement->execute(['id' => $userId]);
    }

    public function resetLoginAttempts(int $userId): void
    {
        $statement = $this->db->prepare('
            UPDATE users
            SET failed_login_attempts = 0,
                last_failed_login_at = NULL,
                last_login_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ');

        $statement->execute(['id' => $userId]);
    }

    public function allForAdmin(): array
    {
        $statement = $this->db->prepare('
            SELECT
                u.id,
                u.email,
                u.role,
                u.is_active,
                u.last_login_at,
                u.created_at,
                p.full_name,
                COALESCE(completed_jobs.aggregate, 0) AS jobs_completed,
                COALESCE(review_stats.avg_rating, 0) AS avg_rating,
                COALESCE(review_stats.review_count, 0) AS review_count
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN (
                SELECT tasker_id, COUNT(*) AS aggregate
                FROM bookings
                WHERE status = :completed_status
                GROUP BY tasker_id
            ) AS completed_jobs ON completed_jobs.tasker_id = u.id
            LEFT JOIN (
                SELECT reviewee_id,
                       AVG(rating) AS avg_rating,
                       COUNT(*) AS review_count
                FROM reviews
                GROUP BY reviewee_id
            ) AS review_stats ON review_stats.reviewee_id = u.id
            ORDER BY u.created_at DESC
        ');
        $statement->execute(['completed_status' => 'completed']);

        return $statement->fetchAll();
    }

    public function setActive(int $userId, bool $isActive): void
    {
        $statement = $this->db->prepare('
            UPDATE users
            SET is_active = :is_active, updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'is_active' => $isActive ? 1 : 0,
            'id' => $userId,
        ]);
    }

    public function countAll(): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) AS aggregate FROM users');
        $statement->execute();

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function countByRole(string $role, bool $onlyActive = false): int
    {
        $sql = 'SELECT COUNT(*) AS aggregate FROM users WHERE role = :role';

        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }

        $statement = $this->db->prepare($sql);
        $statement->execute(['role' => $role]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }
}
