<?php

declare(strict_types=1);

final class AdminAudit
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function log(int $adminUserId, string $targetType, int $targetId, string $action, ?string $notes = null): void
    {
        $statement = $this->db->prepare('
            INSERT INTO admin_audit (admin_user_id, target_type, target_id, action, notes, created_at)
            VALUES (:admin_user_id, :target_type, :target_id, :action, :notes, NOW())
        ');
        $statement->execute([
            'admin_user_id' => $adminUserId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'notes' => $notes,
        ]);
    }

    public function latest(int $limit = 20): array
    {
        $statement = $this->db->prepare('
            SELECT
                aa.*,
                aa.admin_user_id AS admin_id,
                p.full_name AS admin_name
            FROM admin_audit aa
            INNER JOIN profiles p ON p.user_id = aa.admin_user_id
            ORDER BY aa.created_at DESC
            LIMIT :limit
        ');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
