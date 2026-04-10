<?php

declare(strict_types=1);

final class Task
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $postingAccess = PlanFeatureAccess::canCreateTask((int) $data['client_id']);
        if (!$postingAccess['allowed']) {
            throw new RuntimeException((string) $postingAccess['message']);
        }

        $statement = $this->db->prepare('
            INSERT INTO tasks (
                client_id, category_id, title, description, city, region, country, budget, status, scheduled_for, is_active, created_at, updated_at
            ) VALUES (
                :client_id, :category_id, :title, :description, :city, :region, :country, :budget, :status, :scheduled_for, 1, NOW(), NOW()
            )
        ');

        $statement->execute([
            'client_id' => $data['client_id'],
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'city' => $data['city'],
            'region' => $data['region'] !== '' ? $data['region'] : null,
            'country' => $data['country'],
            'budget' => $data['budget'],
            'status' => 'open',
            'scheduled_for' => $data['scheduled_for'] !== '' ? $data['scheduled_for'] : null,
        ]);

        $taskId = (int) $this->db->lastInsertId();

        if ($taskId > 0 && $this->findByIdForClient($taskId, (int) $data['client_id']) !== null) {
            return $taskId;
        }

        $fallbackStatement = $this->db->prepare('
            SELECT id
            FROM tasks
            WHERE client_id = :client_id
              AND category_id = :category_id
              AND title = :title
              AND description = :description
              AND city = :city
              AND (
                    (:region_blank = 1 AND region IS NULL)
                    OR region = :region_value
              )
              AND country = :country
              AND budget = :budget
              AND (
                    (:scheduled_blank = 1 AND scheduled_for IS NULL)
                    OR scheduled_for = :scheduled_for
              )
            ORDER BY id DESC
            LIMIT 1
        ');
        $fallbackStatement->execute([
            'client_id' => $data['client_id'],
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'city' => $data['city'],
            'region_blank' => $data['region'] === '' ? 1 : 0,
            'region_value' => $data['region'] !== '' ? $data['region'] : null,
            'country' => $data['country'],
            'budget' => $data['budget'],
            'scheduled_blank' => $data['scheduled_for'] === '' ? 1 : 0,
            'scheduled_for' => $data['scheduled_for'] !== '' ? $data['scheduled_for'] : null,
        ]);
        $fallbackTask = $fallbackStatement->fetch();

        if ($fallbackTask && isset($fallbackTask['id'])) {
            return (int) $fallbackTask['id'];
        }

        throw new RuntimeException('Task was created, but the new record could not be reloaded safely.');
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                t.*,
                c.name AS category_name,
                p.full_name AS client_name
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            INNER JOIN profiles p ON p.user_id = t.client_id
            WHERE t.id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $task = $statement->fetch();

        return $task ?: null;
    }

    public function findByIdForClient(int $id, int $clientId): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                t.*,
                c.name AS category_name
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            WHERE t.id = :id AND t.client_id = :client_id
            LIMIT 1
        ');
        $statement->execute([
            'id' => $id,
            'client_id' => $clientId,
        ]);

        $task = $statement->fetch();

        return $task ?: null;
    }

    public function findOpenById(int $id, ?array $viewerPlan = null): ?array
    {
        $visibilityClause = '';
        $params = [
            'id' => $id,
            'status' => 'open',
        ];
        if ($viewerPlan !== null) {
            $delayMinutes = max(0, (int) ($viewerPlan['job_alert_delay_minutes'] ?? 0));
            $visibilityClause = ' AND t.created_at <= DATE_SUB(NOW(), INTERVAL :delay_minutes MINUTE)';
            $params['delay_minutes'] = $delayMinutes;
        }

        $statement = $this->db->prepare('
            SELECT
                t.*,
                c.name AS category_name,
                p.full_name AS client_name
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            INNER JOIN profiles p ON p.user_id = t.client_id
            WHERE t.id = :id AND t.is_active = 1 AND t.status = :status' . $visibilityClause . '
            LIMIT 1
        ');
        $statement->execute($params);

        $task = $statement->fetch();

        return $task ?: null;
    }

    public function forClient(int $clientId): array
    {
        $statement = $this->db->prepare('
            SELECT
                t.id,
                t.title,
                t.city,
                t.country,
                t.budget,
                t.status,
                t.scheduled_for,
                t.created_at,
                c.name AS category_name,
                b.id AS booking_id,
                b.tasker_id,
                bid.amount AS agreed_amount,
                p_tasker.full_name AS tasker_name
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            LEFT JOIN bookings b ON b.task_id = t.id AND b.status IN ("active", "completed")
            LEFT JOIN bids bid ON bid.id = b.bid_id
            LEFT JOIN profiles p_tasker ON p_tasker.user_id = b.tasker_id
            WHERE t.client_id = :client_id
            ORDER BY t.created_at DESC
        ');
        $statement->execute(['client_id' => $clientId]);

        return $statement->fetchAll();
    }

    public function browseOpen(array $filters, ?array $viewerPlan = null, int $limit = 25, int $offset = 0): array
    {
        [$sql, $params] = $this->browseOpenBaseQuery($filters, $viewerPlan);
        $sortBy = $filters['sort'] ?? 'newest';
        switch ($sortBy) {
            case 'oldest':
                $sql .= ' ORDER BY client_priority_level DESC, t.created_at ASC';
                break;
            case 'budget_high':
                $sql .= ' ORDER BY client_priority_level DESC, t.budget DESC, t.created_at DESC';
                break;
            case 'budget_low':
                $sql .= ' ORDER BY client_priority_level DESC, t.budget ASC, t.created_at DESC';
                break;
            case 'soonest':
                $sql .= ' ORDER BY client_priority_level DESC, t.scheduled_for ASC, t.created_at DESC';
                break;
            case 'newest':
            default:
                $sql .= ' ORDER BY client_priority_level DESC, t.created_at DESC';
                break;
        }

        $sql .= ' LIMIT :limit OFFSET :offset';

        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countBrowseOpen(array $filters, ?array $viewerPlan = null): int
    {
        [$sql, $params] = $this->browseOpenBaseQuery($filters, $viewerPlan, true);
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function update(array $data): bool
    {
        $statement = $this->db->prepare('
            UPDATE tasks
            SET
                category_id = :category_id,
                title = :title,
                description = :description,
                city = :city,
                region = :region,
                country = :country,
                budget = :budget,
                scheduled_for = :scheduled_for,
                updated_at = NOW()
            WHERE id = :id AND client_id = :client_id AND status = :status
        ');

        return $statement->execute([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'city' => $data['city'],
            'region' => $data['region'] !== '' ? $data['region'] : null,
            'country' => $data['country'],
            'budget' => $data['budget'],
            'scheduled_for' => $data['scheduled_for'] !== '' ? $data['scheduled_for'] : null,
            'id' => $data['id'],
            'client_id' => $data['client_id'],
            'status' => 'open',
        ]);
    }

    public function cancel(int $id, int $clientId): bool
    {
        $statement = $this->db->prepare('
            UPDATE tasks
            SET status = :cancelled_status, updated_at = NOW()
            WHERE id = :id AND client_id = :client_id AND status = :open_status
        ');

        return $statement->execute([
            'cancelled_status' => 'cancelled',
            'open_status' => 'open',
            'id' => $id,
            'client_id' => $clientId,
        ]);
    }

    public function allForAdmin(int $limit = 50, int $offset = 0): array
    {
        $statement = $this->db->prepare('
            SELECT
                t.id,
                t.title,
                t.status,
                t.is_active,
                t.budget,
                t.city,
                t.country,
                t.created_at,
                c.name AS category_name,
                p.full_name AS client_name
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            INNER JOIN profiles p ON p.user_id = t.client_id
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countAllForAdmin(): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) AS aggregate FROM tasks');
        $statement->execute();

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function setActive(int $taskId, bool $isActive): void
    {
        $statement = $this->db->prepare('
            UPDATE tasks
            SET
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'is_active' => $isActive ? 1 : 0,
            'id' => $taskId,
        ]);
    }

    public function countByStatus(string $status): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM tasks
            WHERE status = :status
        ');
        $statement->execute(['status' => $status]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function countActiveByStatus(string $status): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM tasks
            WHERE status = :status AND is_active = 1
        ');
        $statement->execute(['status' => $status]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function countOpenActiveByClientId(int $clientId): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM tasks
            WHERE client_id = :client_id
              AND is_active = 1
              AND status = :status
        ');
        $statement->execute([
            'client_id' => $clientId,
            'status' => 'open',
        ]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    private function browseOpenBaseQuery(array $filters, ?array $viewerPlan = null, bool $countOnly = false): array
    {
        $visibilityClause = '';
        $params = ['status' => 'open'];
        if ($viewerPlan !== null) {
            $delayMinutes = max(0, (int) ($viewerPlan['job_alert_delay_minutes'] ?? 0));
            $visibilityClause = ' AND t.created_at <= DATE_SUB(NOW(), INTERVAL :delay_minutes MINUTE)';
            $params['delay_minutes'] = $delayMinutes;
        }

        $select = $countOnly
            ? 'COUNT(*) AS aggregate'
            : '
                t.id,
                t.title,
                t.description,
                t.city,
                t.region,
                t.country,
                t.budget,
                t.scheduled_for,
                t.created_at,
                c.name AS category_name,
                p.full_name AS client_name,
                COALESCE(plan_visibility.visibility_level, 1) AS client_visibility_level,
                COALESCE(plan_visibility.priority_level, 1) AS client_priority_level,
                COALESCE(plan_visibility.name, "Basic Trial") AS client_plan_name,
                plan_visibility.badge_name AS client_badge_name
            ';

        $sql = '
            SELECT ' . $select . '
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            INNER JOIN profiles p ON p.user_id = t.client_id
        ';

        if (!$countOnly) {
            $sql .= '
            LEFT JOIN subscriptions s ON s.user_id = t.client_id AND s.id = (SELECT MAX(id) FROM subscriptions WHERE user_id = t.client_id)
            LEFT JOIN plans plan_visibility ON plan_visibility.id = COALESCE(s.active_plan_id, s.plan_id)
            ';
        }

        $sql .= '
            WHERE t.is_active = 1
              AND t.status = :status
              ' . $visibilityClause . '
        ';

        if (($filters['q'] ?? '') !== '') {
            $sql .= ' AND (t.title LIKE :q_title OR t.description LIKE :q_description)';
            $params['q_title'] = '%' . $filters['q'] . '%';
            $params['q_description'] = '%' . $filters['q'] . '%';
        }

        if ((int) ($filters['category_id'] ?? 0) > 0) {
            $sql .= ' AND t.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (($filters['city'] ?? '') !== '') {
            $sql .= ' AND t.city LIKE :city';
            $params['city'] = '%' . normalize_whitespace((string) $filters['city']) . '%';
        }

        if (($filters['region'] ?? '') !== '') {
            $sql .= ' AND t.region LIKE :region';
            $params['region'] = '%' . normalize_whitespace((string) $filters['region']) . '%';
        }

        if (($filters['min_budget'] ?? '') !== '') {
            $sql .= ' AND t.budget >= :min_budget';
            $params['min_budget'] = $filters['min_budget'];
        }

        if (($filters['max_budget'] ?? '') !== '') {
            $sql .= ' AND t.budget <= :max_budget';
            $params['max_budget'] = $filters['max_budget'];
        }

        if (($filters['date_from'] ?? '') !== '') {
            $sql .= ' AND t.scheduled_for >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (($filters['date_to'] ?? '') !== '') {
            $sql .= ' AND t.scheduled_for <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        return [$sql, $params];
    }
}
