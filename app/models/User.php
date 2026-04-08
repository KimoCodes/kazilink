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
                INSERT INTO users (email, password_hash, role, is_active, failed_login_attempts, last_failed_login_at, last_login_at, last_seen_at, last_logout_at, created_at, updated_at)
                VALUES (:email, :password_hash, :role, 1, 0, NULL, NULL, NULL, NULL, NOW(), NOW())
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

            if (($data['create_trial_subscription'] ?? true) === true && (string) ($data['role'] ?? '') !== 'admin') {
                (new Subscription())->createTrialForUser($userId);
            }

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
                last_seen_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ');

        $statement->execute(['id' => $userId]);
    }

    public function touchPresence(int $userId): void
    {
        $statement = $this->db->prepare('
            UPDATE users
            SET last_seen_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ');

        $statement->execute(['id' => $userId]);
    }

    public function recordLogout(int $userId): void
    {
        $statement = $this->db->prepare('
            UPDATE users
            SET last_logout_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ');

        $statement->execute(['id' => $userId]);
    }

    public function allForAdmin(int $limit = 50, int $offset = 0): array
    {
        $statement = $this->db->prepare('
            SELECT
                u.id,
                u.email,
                u.role,
                u.is_active,
                u.last_login_at,
                u.last_seen_at,
                u.last_logout_at,
                u.created_at,
                p.full_name,
                COALESCE(active_plan.name, "Basic") AS current_plan_name,
                COALESCE(active_plan.price_rwf, 500) AS current_plan_price_rwf,
                COALESCE(active_plan.visibility_level, 1) AS current_visibility_level,
                active_subscription.status AS subscription_status,
                active_subscription.trial_ends_at,
                active_subscription.current_period_ends_at,
                COALESCE(completed_jobs.aggregate, 0) AS jobs_completed,
                COALESCE(review_stats.avg_rating, 0) AS avg_rating,
                COALESCE(review_stats.review_count, 0) AS review_count
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN (
                SELECT s1.*
                FROM subscriptions s1
                INNER JOIN (
                    SELECT user_id, MAX(id) AS max_id
                    FROM subscriptions
                    GROUP BY user_id
                ) latest ON latest.max_id = s1.id
            ) AS active_subscription ON active_subscription.user_id = u.id
            LEFT JOIN plans active_plan ON active_plan.id = COALESCE(active_subscription.active_plan_id, active_subscription.plan_id)
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
            LIMIT :limit OFFSET :offset
        ');
        $statement->bindValue(':completed_status', 'completed');
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

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

    public function listActiveTaskersWithStats(int $limit = 8): array
    {
        $statement = $this->db->prepare('
            SELECT
                u.id,
                u.email,
                p.full_name,
                p.city,
                p.region,
                p.country,
                p.bio,
                p.skills_summary,
                p.avatar_path,
                COALESCE(plan_visibility.visibility_level, 1) AS visibility_level,
                COALESCE(plan_visibility.plan_name, "Basic") AS visibility_plan_name,
                COALESCE(review_stats.avg_rating, 0) AS avg_rating,
                COALESCE(review_stats.review_count, 0) AS review_count,
                COALESCE(completed_jobs.aggregate, 0) AS completed_jobs
            FROM users u
            INNER JOIN profiles p ON p.user_id = u.id
            LEFT JOIN (
                SELECT
                    s.user_id,
                    p2.visibility_level,
                    p2.name AS plan_name
                FROM subscriptions s
                INNER JOIN plans p2 ON p2.id = COALESCE(s.active_plan_id, s.plan_id)
                INNER JOIN (
                    SELECT user_id, MAX(id) AS max_id
                    FROM subscriptions
                    GROUP BY user_id
                ) latest ON latest.max_id = s.id
            ) AS plan_visibility ON plan_visibility.user_id = u.id
            LEFT JOIN (
                SELECT reviewee_id,
                       AVG(rating) AS avg_rating,
                       COUNT(*) AS review_count
                FROM reviews
                GROUP BY reviewee_id
            ) AS review_stats ON review_stats.reviewee_id = u.id
            LEFT JOIN (
                SELECT tasker_id, COUNT(*) AS aggregate
                FROM bookings
                WHERE status = :completed_status
                GROUP BY tasker_id
            ) AS completed_jobs ON completed_jobs.tasker_id = u.id
            WHERE u.role = :role
              AND u.is_active = 1
            ORDER BY plan_visibility.visibility_level DESC, review_stats.avg_rating DESC, review_stats.review_count DESC, completed_jobs.aggregate DESC, p.full_name ASC
            LIMIT :limit
        ');
        $statement->bindValue(':completed_status', 'completed');
        $statement->bindValue(':role', 'tasker');
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
