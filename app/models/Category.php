<?php

declare(strict_types=1);

final class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function allActive(): array
    {
        $statement = $this->db->prepare('
            SELECT id, name, slug
            FROM categories
            WHERE is_active = 1
            ORDER BY name ASC
        ');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function activeIds(): array
    {
        return array_map(
            static fn (array $category): int => (int) $category['id'],
            $this->allActive()
        );
    }
}
