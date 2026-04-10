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
                (SELECT COUNT(*) FROM bookings WHERE tasker_id = u.id AND status = :completed_status) AS jobs_completed,
                (SELECT AVG(rating) FROM reviews WHERE reviewee_id = u.id) AS avg_rating,
                (SELECT COUNT(*) FROM reviews WHERE reviewee_id = u.id) AS review_count
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN subscriptions active_subscription ON active_subscription.user_id = u.id 
                AND active_subscription.id = (SELECT MAX(id) FROM subscriptions WHERE user_id = u.id)
            LEFT JOIN plans active_plan ON active_plan.id = COALESCE(active_subscription.active_plan_id, active_subscription.plan_id)
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
                COALESCE(p2.visibility_level, 1) AS visibility_level,
                COALESCE(p2.name, "Basic") AS visibility_plan_name,
                COALESCE((SELECT AVG(rating) FROM reviews WHERE reviewee_id = u.id), 0) AS avg_rating,
                COALESCE((SELECT COUNT(*) FROM reviews WHERE reviewee_id = u.id), 0) AS review_count,
                COALESCE((SELECT COUNT(*) FROM bookings WHERE tasker_id = u.id AND status = :completed_status), 0) AS completed_jobs
            FROM users u
            INNER JOIN profiles p ON p.user_id = u.id
            LEFT JOIN subscriptions s ON s.user_id = u.id AND s.id = (SELECT MAX(id) FROM subscriptions WHERE user_id = u.id)
            LEFT JOIN plans p2 ON p2.id = COALESCE(s.active_plan_id, s.plan_id)
            WHERE u.role = :role
              AND u.is_active = 1
            ORDER BY visibility_level DESC, avg_rating DESC, review_count DESC, completed_jobs DESC, p.full_name ASC
            LIMIT :limit
        ');
        $statement->bindValue(':completed_status', 'completed');
        $statement->bindValue(':role', 'tasker');
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
