<?php

declare(strict_types=1);

final class Subscription
{
    private PDO $db;
    private Plan $plans;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->plans = new Plan();
    }

    public function currentForUser(int $userId): ?array
    {
        if (!Database::tableExists('subscriptions') || !Database::tableExists('plans')) {
            return null;
        }

        $statement = $this->db->prepare('
            SELECT
                s.*,
                p.slug AS plan_slug,
                p.name AS plan_name,
                p.price_rwf,
                p.visibility_level,
                p.max_applications_per_day,
                p.priority_level,
                p.job_alert_delay_minutes,
                p.max_active_jobs,
                p.commission_discount,
                p.badge_name,
                p.active AS plan_active
            FROM subscriptions s
            INNER JOIN plans p ON p.id = COALESCE(s.active_plan_id, s.plan_id)
            WHERE s.user_id = :user_id
            ORDER BY s.updated_at DESC, s.id DESC
            LIMIT 1
        ');
        $statement->execute(['user_id' => $userId]);
        $subscription = $statement->fetch();

        return $subscription ?: null;
    }

    public function latestForAdmin(int $limit = 100, int $offset = 0): array
    {
        if (!Database::tableExists('subscriptions') || !Database::tableExists('plans')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT
                s.*,
                p.slug AS plan_slug,
                p.name AS plan_name,
                p.price_rwf,
                p.visibility_level,
                p.max_applications_per_day,
                p.priority_level,
                p.job_alert_delay_minutes,
                p.max_active_jobs,
                p.commission_discount,
                p.badge_name,
                u.email,
                pr.full_name,
                COALESCE(um.daily_applications_count, 0) AS daily_applications_count,
                um.last_reset_date
            FROM subscriptions s
            INNER JOIN plans p ON p.id = COALESCE(s.active_plan_id, s.plan_id)
            INNER JOIN users u ON u.id = s.user_id
            INNER JOIN profiles pr ON pr.user_id = u.id
            LEFT JOIN user_metrics um ON um.user_id = u.id
            ORDER BY s.updated_at DESC, s.id DESC
            LIMIT :limit OFFSET :offset
        ');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countForAdmin(): int
    {
        if (!Database::tableExists('subscriptions')) {
            return 0;
        }

        $statement = $this->db->prepare('SELECT COUNT(*) AS aggregate FROM subscriptions');
        $statement->execute();

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function createTrialForUser(int $userId): int
    {
        if (!Database::tableExists('subscriptions') || !Database::tableExists('plans')) {
            throw new RuntimeException('Subscription tables do not exist yet. Run the subscription migration first.');
        }

        $existing = $this->currentForUser($userId);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $basicPlan = $this->plans->getBasicPlan();
        if ($basicPlan === null) {
            throw new RuntimeException('Basic plan is missing. Seed or create plans before registering users.');
        }

        $statement = $this->db->prepare('
            INSERT INTO subscriptions (user_id, plan_id, active_plan_id, pending_plan_id, status, trial_ends_at, current_period_ends_at, momo_reference, created_at, updated_at)
            VALUES (:user_id, :plan_id, :active_plan_id, NULL, :status, DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), NULL, NOW(), NOW())
        ');
        $statement->execute([
            'user_id' => $userId,
            'plan_id' => $basicPlan['id'],
            'active_plan_id' => $basicPlan['id'],
            'status' => 'trialing',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function markPaymentPending(int $subscriptionId, int $planId, string $momoReference): void
    {
        if (!Database::tableExists('subscriptions')) {
            throw new RuntimeException('The subscriptions table does not exist yet. Run the subscription migration first.');
        }

        $statement = $this->db->prepare('
            UPDATE subscriptions
            SET pending_plan_id = :pending_plan_id,
                momo_reference = :momo_reference,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $subscriptionId,
            'pending_plan_id' => $planId,
            'momo_reference' => $momoReference,
        ]);
    }

    public function activateFromSuccessfulPayment(int $userId, int $planId, string $momoReference): void
    {
        if (!Database::tableExists('subscriptions')) {
            throw new RuntimeException('The subscriptions table does not exist yet. Run the subscription migration first.');
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare('
                SELECT *
                FROM subscriptions
                WHERE user_id = :user_id
                ORDER BY updated_at DESC, id DESC
                LIMIT 1
                FOR UPDATE
            ');
            $statement->execute(['user_id' => $userId]);
            $subscription = $statement->fetch();
            if ($subscription === null) {
                $this->createTrialForUser($userId);
                $statement->execute(['user_id' => $userId]);
                $subscription = $statement->fetch();
            }

            if ($subscription === null) {
                throw new RuntimeException('Unable to load subscription after trial creation.');
            }

            if ((string) ($subscription['momo_reference'] ?? '') === $momoReference && (string) $subscription['status'] === 'active') {
                $this->db->commit();

                return;
            }

            if ((string) ($subscription['momo_reference'] ?? '') !== $momoReference) {
                throw new RuntimeException('Payment reference does not match the pending subscription.');
            }

            $pendingPlanId = (int) ($subscription['pending_plan_id'] ?? 0);
            if ($pendingPlanId <= 0 || $pendingPlanId !== $planId) {
                throw new RuntimeException('Payment does not match the pending subscription plan.');
            }

            $anchorTime = 'NOW()';
            if (!empty($subscription['trial_ends_at']) && strtotime((string) $subscription['trial_ends_at']) > time()) {
                $anchorTime = 'trial_ends_at';
            } elseif (!empty($subscription['current_period_ends_at']) && strtotime((string) $subscription['current_period_ends_at']) > time()) {
                $anchorTime = 'current_period_ends_at';
            }

            $statement = $this->db->prepare('
                UPDATE subscriptions
                SET plan_id = :plan_id,
                    active_plan_id = :active_plan_id,
                    pending_plan_id = NULL,
                    status = :status,
                    momo_reference = :momo_reference,
                    current_period_ends_at = DATE_ADD(' . $anchorTime . ', INTERVAL 1 MONTH),
                    updated_at = NOW()
                WHERE id = :id
            ');
            $statement->execute([
                'id' => $subscription['id'],
                'plan_id' => $planId,
                'active_plan_id' => $planId,
                'status' => 'active',
                'momo_reference' => $momoReference,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function markPastDueIfExpired(int $subscriptionId): void
    {
        if (!Database::tableExists('subscriptions')) {
            return;
        }

        $statement = $this->db->prepare('
            UPDATE subscriptions
            SET status = :status,
                pending_plan_id = NULL,
                updated_at = NOW()
            WHERE id = :id
              AND status = :active_status
              AND current_period_ends_at IS NOT NULL
              AND current_period_ends_at < NOW()
        ');
        $statement->execute([
            'id' => $subscriptionId,
            'status' => 'past_due',
            'active_status' => 'active',
        ]);
    }

    public function setStatus(int $subscriptionId, string $status): void
    {
        if (!Database::tableExists('subscriptions')) {
            throw new RuntimeException('The subscriptions table does not exist yet. Run the subscription migration first.');
        }

        $statement = $this->db->prepare('
            UPDATE subscriptions
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $subscriptionId,
            'status' => $status,
        ]);
    }
}
