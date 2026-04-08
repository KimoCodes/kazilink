<?php

declare(strict_types=1);

final class Ad
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function activeByPlacement(string $placement, int $limit = 3, bool $includeHomeFallback = false): array
    {
        if (!Database::tableExists('ads')) {
            return [];
        }

        $placements = [$placement];

        if ($includeHomeFallback && $placement !== 'home') {
            $placements[] = 'home';
        }

        $placeholders = [];
        $params = [];

        foreach ($placements as $index => $placementValue) {
            $key = ':placement_' . $index;
            $placeholders[] = $key;
            $params[$key] = $placementValue;
        }

        $statement = $this->db->prepare(sprintf(
            '
            SELECT *
            FROM ads
            WHERE placement IN (%s)
              AND is_active = 1
            ORDER BY CASE WHEN placement = :primary_placement THEN 0 ELSE 1 END, sort_order ASC, id ASC
            LIMIT :limit
        ',
            implode(', ', $placeholders)
        ));

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->bindValue(':primary_placement', $placement);
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function allForAdmin(): array
    {
        if (!Database::tableExists('ads')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM ads
            ORDER BY placement ASC, sort_order ASC, id ASC
        ');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        if (!Database::tableExists('ads')) {
            return null;
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM ads
            WHERE id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $ad = $statement->fetch();

        return $ad ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare('
            INSERT INTO ads (title, body, media_type, media_path, cta_label, cta_url, placement, sort_order, is_active, created_at, updated_at)
            VALUES (:title, :body, :media_type, :media_path, :cta_label, :cta_url, :placement, :sort_order, :is_active, NOW(), NOW())
        ');
        $statement->execute([
            'title' => $data['title'],
            'body' => $data['body'],
            'media_type' => $data['media_type'],
            'media_path' => $data['media_path'],
            'cta_label' => $data['cta_label'] !== '' ? $data['cta_label'] : null,
            'cta_url' => $data['cta_url'] !== '' ? $data['cta_url'] : null,
            'placement' => $data['placement'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->db->prepare('
            UPDATE ads
            SET title = :title,
                body = :body,
                media_type = :media_type,
                media_path = :media_path,
                cta_label = :cta_label,
                cta_url = :cta_url,
                placement = :placement,
                sort_order = :sort_order,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'title' => $data['title'],
            'body' => $data['body'],
            'media_type' => $data['media_type'],
            'media_path' => $data['media_path'],
            'cta_label' => $data['cta_label'] !== '' ? $data['cta_label'] : null,
            'cta_url' => $data['cta_url'] !== '' ? $data['cta_url'] : null,
            'placement' => $data['placement'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'] ? 1 : 0,
        ]);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $statement = $this->db->prepare('
            UPDATE ads
            SET is_active = :is_active, updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'is_active' => $isActive ? 1 : 0,
        ]);
    }

    public function hasPlacementSortOrderConflict(string $placement, int $sortOrder, int $ignoreId = 0): bool
    {
        $sql = '
            SELECT id
            FROM ads
            WHERE placement = :placement
              AND sort_order = :sort_order
        ';
        $params = [
            'placement' => $placement,
            'sort_order' => $sortOrder,
        ];

        if ($ignoreId > 0) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }
}
