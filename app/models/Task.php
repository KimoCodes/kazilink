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

        return (int) $this->db->lastInsertId();
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

    public function findOpenById(int $id): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                t.*,
                c.name AS category_name,
                p.full_name AS client_name
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            INNER JOIN profiles p ON p.user_id = t.client_id
            WHERE t.id = :id AND t.is_active = 1 AND t.status = :status
            LIMIT 1
        ');
        $statement->execute([
            'id' => $id,
            'status' => 'open',
        ]);

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
                b.tasker_id,
                p_tasker.full_name AS tasker_name
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            LEFT JOIN bookings b ON b.task_id = t.id AND b.status IN ("active", "completed")
            LEFT JOIN profiles p_tasker ON p_tasker.user_id = b.tasker_id
            WHERE t.client_id = :client_id
            ORDER BY t.created_at DESC
        ');
        $statement->execute(['client_id' => $clientId]);

        return $statement->fetchAll();
    }

    public function browseOpen(array $filters): array
    {
        $sql = '
            SELECT
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
                p.full_name AS client_name
            FROM tasks t
            INNER JOIN categories c ON c.id = t.category_id
            INNER JOIN profiles p ON p.user_id = t.client_id
            WHERE t.is_active = 1
              AND t.status = :status
        ';

        $params = ['status' => 'open'];

        if ($filters['q'] !== '') {
            $sql .= ' AND (t.title LIKE :q_title OR t.description LIKE :q_description)';
            $params['q_title'] = '%' . $filters['q'] . '%';
            $params['q_description'] = '%' . $filters['q'] . '%';
        }

        if ($filters['category_id'] > 0) {
            $sql .= ' AND t.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }

        if ($filters['city'] !== '') {
            $sql .= ' AND TRIM(LOWER(t.city)) LIKE :city';
            $params['city'] = '%' . mb_strtolower(normalize_whitespace((string) $filters['city'])) . '%';
        }

        if ($filters['region'] !== '') {
            $sql .= ' AND TRIM(LOWER(t.region)) LIKE :region';
            $params['region'] = '%' . mb_strtolower(normalize_whitespace((string) $filters['region'])) . '%';
        }

        if ($filters['min_budget'] !== '') {
            $sql .= ' AND t.budget >= :min_budget';
            $params['min_budget'] = $filters['min_budget'];
        }

        if ($filters['max_budget'] !== '') {
            $sql .= ' AND t.budget <= :max_budget';
            $params['max_budget'] = $filters['max_budget'];
        }

        // Date filtering
        if ($filters['date_from'] !== '') {
            $sql .= ' AND DATE(t.scheduled_for) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if ($filters['date_to'] !== '') {
            $sql .= ' AND DATE(t.scheduled_for) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        // Sorting
        $sortBy = $filters['sort'] ?? 'newest';
        switch ($sortBy) {
            case 'oldest':
                $sql .= ' ORDER BY t.created_at ASC';
                break;
            case 'budget_high':
                $sql .= ' ORDER BY t.budget DESC';
                break;
            case 'budget_low':
                $sql .= ' ORDER BY t.budget ASC';
                break;
            case 'soonest':
                $sql .= ' ORDER BY t.scheduled_for ASC';
                break;
            case 'newest':
            default:
                $sql .= ' ORDER BY t.created_at DESC';
                break;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function update(array $data): void
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

        $statement->execute([
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

    public function cancel(int $id, int $clientId): void
    {
        $statement = $this->db->prepare('
            UPDATE tasks
            SET status = :cancelled_status, updated_at = NOW()
            WHERE id = :id AND client_id = :client_id AND status = :open_status
        ');

        $statement->execute([
            'cancelled_status' => 'cancelled',
            'open_status' => 'open',
            'id' => $id,
            'client_id' => $clientId,
        ]);
    }

    public function allForAdmin(): array
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
        ');
        $statement->execute();

        return $statement->fetchAll();
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
}
