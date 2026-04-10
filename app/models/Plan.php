<?php

declare(strict_types=1);

final class Plan
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function allActive(): array
    {
        if (!Database::tableExists('plans')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM plans
            WHERE active = 1
            ORDER BY priority_level ASC, price_rwf ASC, name ASC
        ');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function allForAdmin(): array
    {
        if (!Database::tableExists('plans')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT
                p.*,
                (
                    SELECT COUNT(*)
                    FROM subscriptions s
                    WHERE COALESCE(s.active_plan_id, s.plan_id) = p.id
                      AND s.status IN ("trialing", "active", "past_due")
                ) AS subscription_count
            FROM plans p
            ORDER BY p.priority_level ASC, p.created_at ASC
        ');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        if (!Database::tableExists('plans')) {
            return null;
        }

        $statement = $this->db->prepare('SELECT * FROM plans WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $plan = $statement->fetch();

        return $plan ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        if (!Database::tableExists('plans')) {
            return null;
        }

        $statement = $this->db->prepare('SELECT * FROM plans WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $plan = $statement->fetch();

        return $plan ?: null;
    }

    public function getBasicPlan(): ?array
    {
        return $this->findBySlug('basic');
    }

    public function create(array $data): int
    {
        if (!Database::tableExists('plans')) {
            throw new RuntimeException('The plans table does not exist yet. Run the subscription migration first.');
        }

        $statement = $this->db->prepare('
            INSERT INTO plans (
                slug,
                name,
                price_rwf,
                visibility_level,
                max_applications_per_day,
                priority_level,
                job_alert_delay_minutes,
                max_active_jobs,
                commission_discount,
                badge_name,
                active,
                created_at,
                updated_at
            )
            VALUES (
                :slug,
                :name,
                :price_rwf,
                :visibility_level,
                :max_applications_per_day,
                :priority_level,
                :job_alert_delay_minutes,
                :max_active_jobs,
                :commission_discount,
                :badge_name,
                :active,
                NOW(),
                NOW()
            )
        ');
        $statement->execute([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'price_rwf' => $data['price_rwf'],
            'visibility_level' => $data['visibility_level'],
            'max_applications_per_day' => $data['max_applications_per_day'],
            'priority_level' => $data['priority_level'],
            'job_alert_delay_minutes' => $data['job_alert_delay_minutes'],
            'max_active_jobs' => $data['max_active_jobs'],
            'commission_discount' => $data['commission_discount'],
            'badge_name' => $data['badge_name'] !== '' ? $data['badge_name'] : null,
            'active' => $data['active'] ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        if (!Database::tableExists('plans')) {
            throw new RuntimeException('The plans table does not exist yet. Run the subscription migration first.');
        }

        $statement = $this->db->prepare('
            UPDATE plans
            SET slug = :slug,
                name = :name,
                price_rwf = :price_rwf,
                visibility_level = :visibility_level,
                max_applications_per_day = :max_applications_per_day,
                priority_level = :priority_level,
                job_alert_delay_minutes = :job_alert_delay_minutes,
                max_active_jobs = :max_active_jobs,
                commission_discount = :commission_discount,
                badge_name = :badge_name,
                active = :active,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'slug' => $data['slug'],
            'name' => $data['name'],
            'price_rwf' => $data['price_rwf'],
            'visibility_level' => $data['visibility_level'],
            'max_applications_per_day' => $data['max_applications_per_day'],
            'priority_level' => $data['priority_level'],
            'job_alert_delay_minutes' => $data['job_alert_delay_minutes'],
            'max_active_jobs' => $data['max_active_jobs'],
            'commission_discount' => $data['commission_discount'],
            'badge_name' => $data['badge_name'] !== '' ? $data['badge_name'] : null,
            'active' => $data['active'] ? 1 : 0,
        ]);
    }
}
