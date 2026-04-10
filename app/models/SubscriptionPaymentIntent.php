<?php

declare(strict_types=1);

final class SubscriptionPaymentIntent
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACTIVATED = 'activated';
    public const STATUS_EXPIRED = 'expired';

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createDraft(array $data): int
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            throw new RuntimeException('The subscription payment tables do not exist yet. Run the manual payment migration first.');
        }

        $statement = $this->db->prepare('
            INSERT INTO subscription_payment_intents (
                reference,
                plan_id,
                user_id,
                amount_expected_rwf,
                amount_paid_rwf,
                momo_number_displayed,
                payer_phone,
                screenshot_url,
                screenshot_hash,
                submitted_at,
                intended_activation_at,
                deadline_at,
                status,
                is_late,
                reviewed_by,
                reviewed_at,
                rejection_reason,
                activated_at,
                created_at,
                updated_at
            )
            VALUES (
                :reference,
                :plan_id,
                :user_id,
                :amount_expected_rwf,
                NULL,
                :momo_number_displayed,
                NULL,
                NULL,
                NULL,
                NULL,
                :intended_activation_at,
                :deadline_at,
                :status,
                0,
                NULL,
                NULL,
                NULL,
                NULL,
                NOW(),
                NOW()
            )
        ');
        $statement->execute([
            'reference' => $data['reference'],
            'plan_id' => $data['plan_id'],
            'user_id' => $data['user_id'],
            'amount_expected_rwf' => $data['amount_expected_rwf'],
            'momo_number_displayed' => $data['momo_number_displayed'],
            'intended_activation_at' => $data['intended_activation_at'],
            'deadline_at' => $data['deadline_at'],
            'status' => self::STATUS_DRAFT,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return null;
        }

        $statement = $this->db->prepare('
            SELECT spi.*, plans.name AS plan_name, plans.slug AS plan_slug, profiles.full_name, users.email
            FROM subscription_payment_intents spi
            INNER JOIN plans ON plans.id = spi.plan_id
            INNER JOIN users ON users.id = spi.user_id
            LEFT JOIN profiles ON profiles.user_id = spi.user_id
            WHERE spi.id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $intent = $statement->fetch();

        return $intent ?: null;
    }

    public function findByIdForUser(int $id, int $userId): ?array
    {
        $intent = $this->findById($id);

        if ($intent === null || (int) $intent['user_id'] !== $userId) {
            return null;
        }

        return $intent;
    }

    public function latestForUser(int $userId, int $limit = 10): array
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT spi.*, plans.name AS plan_name
            FROM subscription_payment_intents spi
            INNER JOIN plans ON plans.id = spi.plan_id
            WHERE spi.user_id = :user_id
            ORDER BY spi.created_at DESC, spi.id DESC
            LIMIT :limit
        ');
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function latestOpenForUser(int $userId): ?array
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return null;
        }

        $statement = $this->db->prepare('
            SELECT spi.*, plans.name AS plan_name
            FROM subscription_payment_intents spi
            INNER JOIN plans ON plans.id = spi.plan_id
            WHERE spi.user_id = :user_id
              AND (
                    spi.status = :draft
                 OR spi.status = :pending
                 OR spi.status = :rejected
                 OR spi.status = :approved
              )
            ORDER BY spi.updated_at DESC, spi.id DESC
            LIMIT 1
        ');
        $statement->execute([
            'user_id' => $userId,
            'draft' => self::STATUS_DRAFT,
            'pending' => self::STATUS_PENDING_VERIFICATION,
            'rejected' => self::STATUS_REJECTED,
            'approved' => self::STATUS_APPROVED,
        ]);
        $intent = $statement->fetch();

        return $intent ?: null;
    }

    public function countCreatedByUserSince(int $userId, string $since): int
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return 0;
        }

        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM subscription_payment_intents
            WHERE user_id = :user_id
              AND created_at >= :since
        ');
        $statement->execute([
            'user_id' => $userId,
            'since' => $since,
        ]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function findByScreenshotHash(string $hash, int $excludeId = 0): ?array
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return null;
        }

        $sql = '
            SELECT *
            FROM subscription_payment_intents
            WHERE screenshot_hash = :hash
        ';
        $params = ['hash' => $hash];

        if ($excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY id DESC LIMIT 1';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $intent = $statement->fetch();

        return $intent ?: null;
    }

    public function updateProof(int $id, array $data): void
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            throw new RuntimeException('The subscription payment tables do not exist yet. Run the manual payment migration first.');
        }

        $statement = $this->db->prepare('
            UPDATE subscription_payment_intents
            SET amount_paid_rwf = :amount_paid_rwf,
                payer_phone = :payer_phone,
                screenshot_url = :screenshot_url,
                screenshot_hash = :screenshot_hash,
                rejection_reason = NULL,
                reviewed_by = NULL,
                reviewed_at = NULL,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'amount_paid_rwf' => $data['amount_paid_rwf'],
            'payer_phone' => $data['payer_phone'],
            'screenshot_url' => $data['screenshot_url'],
            'screenshot_hash' => $data['screenshot_hash'],
        ]);
    }

    public function transitionSubmission(int $id, string $submittedAt, bool $isLate): void
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            throw new RuntimeException('The subscription payment tables do not exist yet. Run the manual payment migration first.');
        }

        $statement = $this->db->prepare('
            UPDATE subscription_payment_intents
            SET submitted_at = :submitted_at,
                status = :status,
                is_late = :is_late,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'submitted_at' => $submittedAt,
            'status' => self::STATUS_PENDING_VERIFICATION,
            'is_late' => $isLate ? 1 : 0,
        ]);
    }

    public function approve(int $id, int $adminUserId, string $reviewedAt): void
    {
        $statement = $this->db->prepare('
            UPDATE subscription_payment_intents
            SET status = :status,
                reviewed_by = :reviewed_by,
                reviewed_at = :reviewed_at,
                rejection_reason = NULL,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $adminUserId,
            'reviewed_at' => $reviewedAt,
        ]);
    }

    public function reject(int $id, int $adminUserId, string $reviewedAt, string $reason): void
    {
        $statement = $this->db->prepare('
            UPDATE subscription_payment_intents
            SET status = :status,
                reviewed_by = :reviewed_by,
                reviewed_at = :reviewed_at,
                rejection_reason = :rejection_reason,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $adminUserId,
            'reviewed_at' => $reviewedAt,
            'rejection_reason' => $reason,
        ]);
    }

    public function markActivated(int $id, string $activatedAt): void
    {
        $statement = $this->db->prepare('
            UPDATE subscription_payment_intents
            SET status = :status,
                activated_at = :activated_at,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'status' => self::STATUS_ACTIVATED,
            'activated_at' => $activatedAt,
        ]);
    }

    public function expireStale(string $now): int
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return 0;
        }

        $statement = $this->db->prepare('
            UPDATE subscription_payment_intents
            SET status = :expired,
                updated_at = NOW()
            WHERE status IN (:draft, :pending, :rejected)
              AND intended_activation_at < :now_at
        ');
        $statement->execute([
            'expired' => self::STATUS_EXPIRED,
            'draft' => self::STATUS_DRAFT,
            'pending' => self::STATUS_PENDING_VERIFICATION,
            'rejected' => self::STATUS_REJECTED,
            'now_at' => $now,
        ]);

        return $statement->rowCount();
    }

    public function dueForActivation(string $now, int $limit = 100): array
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM subscription_payment_intents
            WHERE status = :status
              AND is_late = 0
              AND intended_activation_at <= :now_at
            ORDER BY intended_activation_at ASC, id ASC
            LIMIT :limit
        ');
        $statement->bindValue(':status', self::STATUS_APPROVED);
        $statement->bindValue(':now_at', $now);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function pendingForAdmin(int $limit = 100, int $offset = 0): array
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT
                spi.*,
                plans.name AS plan_name,
                profiles.full_name,
                users.email,
                reviewer_profile.full_name AS reviewer_name
            FROM subscription_payment_intents spi
            INNER JOIN plans ON plans.id = spi.plan_id
            INNER JOIN users ON users.id = spi.user_id
            LEFT JOIN profiles ON profiles.user_id = spi.user_id
            LEFT JOIN profiles reviewer_profile ON reviewer_profile.user_id = spi.reviewed_by
            WHERE spi.status = :status
            ORDER BY spi.submitted_at ASC, spi.id ASC
            LIMIT :limit OFFSET :offset
        ');
        $statement->bindValue(':status', self::STATUS_PENDING_VERIFICATION);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function recentForAdmin(int $limit = 40): array
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT
                spi.*,
                plans.name AS plan_name,
                profiles.full_name,
                users.email,
                reviewer_profile.full_name AS reviewer_name
            FROM subscription_payment_intents spi
            INNER JOIN plans ON plans.id = spi.plan_id
            INNER JOIN users ON users.id = spi.user_id
            LEFT JOIN profiles ON profiles.user_id = spi.user_id
            LEFT JOIN profiles reviewer_profile ON reviewer_profile.user_id = spi.reviewed_by
            ORDER BY spi.updated_at DESC, spi.id DESC
            LIMIT :limit
        ');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countPendingForAdmin(): int
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return 0;
        }

        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM subscription_payment_intents
            WHERE status = :status
        ');
        $statement->execute(['status' => self::STATUS_PENDING_VERIFICATION]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function expiringSoonCandidates(string $windowStart, string $windowEnd, int $limit = 100): array
    {
        if (!Database::tableExists('subscription_payment_intents')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT
                spi.*,
                plans.name AS plan_name,
                plans.slug AS plan_slug,
                users.email,
                profiles.full_name
            FROM subscription_payment_intents spi
            INNER JOIN plans ON plans.id = spi.plan_id
            INNER JOIN users ON users.id = spi.user_id
            LEFT JOIN profiles ON profiles.user_id = spi.user_id
            WHERE (spi.status = :submitted OR spi.status = :pending)
              AND spi.deadline_at >= :window_start
              AND spi.deadline_at <= :window_end
              AND spi.status <> :approved
              AND spi.status <> :rejected
              AND spi.status <> :expired
            ORDER BY spi.deadline_at ASC, spi.id ASC
            LIMIT :limit
        ');
        $statement->bindValue(':submitted', self::STATUS_SUBMITTED);
        $statement->bindValue(':pending', self::STATUS_PENDING_VERIFICATION);
        $statement->bindValue(':approved', self::STATUS_APPROVED);
        $statement->bindValue(':rejected', self::STATUS_REJECTED);
        $statement->bindValue(':expired', self::STATUS_EXPIRED);
        $statement->bindValue(':window_start', $windowStart);
        $statement->bindValue(':window_end', $windowEnd);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
