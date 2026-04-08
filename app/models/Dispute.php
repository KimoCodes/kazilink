<?php

declare(strict_types=1);

final class Dispute
{
    private PDO $db;
    private HiringAgreement $agreements;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
        $this->agreements = new HiringAgreement($this->db);
    }

    public function create(int $agreementId, int $reporterUserId, string $type, string $description): int
    {
        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare('
                INSERT INTO disputes (
                    agreement_id,
                    reporter_user_id,
                    type,
                    description,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :agreement_id,
                    :reporter_user_id,
                    :type,
                    :description,
                    :status,
                    NOW(),
                    NOW()
                )
            ');
            $statement->execute([
                'agreement_id' => $agreementId,
                'reporter_user_id' => $reporterUserId,
                'type' => $type,
                'description' => $description,
                'status' => 'open',
            ]);

            $disputeId = (int) $this->db->lastInsertId();

            $this->agreements->markDisputed($agreementId);
            $this->agreements->logEvent($agreementId, $reporterUserId, 'dispute_opened', [
                'dispute_id' => $disputeId,
                'type' => $type,
                'description' => $description,
                'ip_address' => request_ip(),
                'user_agent' => request_user_agent(),
            ]);

            $this->db->commit();

            return $disputeId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function forAgreement(int $agreementId): array
    {
        $statement = $this->db->prepare('
            SELECT
                d.*,
                p.full_name AS reporter_name,
                u.email AS reporter_email
            FROM disputes d
            INNER JOIN users u ON u.id = d.reporter_user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE d.agreement_id = :agreement_id
            ORDER BY d.created_at DESC, d.id DESC
        ');
        $statement->execute(['agreement_id' => $agreementId]);

        return $statement->fetchAll();
    }

    public function findVisibleById(int $disputeId, int $userId, string $role): ?array
    {
        $params = ['id' => $disputeId];
        $sql = '
            SELECT
                d.*,
                ha.agreement_uid,
                ha.job_title,
                ha.status AS agreement_status,
                ha.client_user_id,
                ha.tasker_user_id,
                p.full_name AS reporter_name,
                u.email AS reporter_email,
                admin_profile.full_name AS admin_updated_by_name
            FROM disputes d
            INNER JOIN hiring_agreements ha ON ha.id = d.agreement_id
            INNER JOIN users u ON u.id = d.reporter_user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN profiles admin_profile ON admin_profile.user_id = d.admin_updated_by
            WHERE d.id = :id
        ';

        if ($role !== 'admin') {
            $sql .= ' AND (ha.client_user_id = :client_user_id OR ha.tasker_user_id = :tasker_user_id)';
            $params['client_user_id'] = $userId;
            $params['tasker_user_id'] = $userId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $dispute = $statement->fetch();

        return $dispute ?: null;
    }

    public function countByStatus(string $status): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM disputes
            WHERE status = :status
        ');
        $statement->execute(['status' => $status]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function latestForAdmin(int $limit = 50): array
    {
        $statement = $this->db->prepare('
            SELECT
                d.*,
                ha.agreement_uid,
                ha.job_title,
                ha.client_user_id,
                ha.tasker_user_id,
                reporter_profile.full_name AS reporter_name,
                client_profile.full_name AS client_name,
                tasker_profile.full_name AS tasker_name,
                admin_profile.full_name AS admin_updated_by_name
            FROM disputes d
            INNER JOIN hiring_agreements ha ON ha.id = d.agreement_id
            LEFT JOIN profiles reporter_profile ON reporter_profile.user_id = d.reporter_user_id
            LEFT JOIN profiles client_profile ON client_profile.user_id = ha.client_user_id
            LEFT JOIN profiles tasker_profile ON tasker_profile.user_id = ha.tasker_user_id
            LEFT JOIN profiles admin_profile ON admin_profile.user_id = d.admin_updated_by
            ORDER BY d.updated_at DESC, d.id DESC
            LIMIT :limit
        ');
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function updateStatus(int $disputeId, string $status, ?string $adminNotes, int $adminUserId): void
    {
        if (!in_array($status, ['open', 'under_review', 'resolved', 'rejected'], true)) {
            throw new RuntimeException('Invalid dispute status.');
        }

        $statement = $this->db->prepare('
            UPDATE disputes
            SET
                status = :status,
                admin_notes = :admin_notes,
                admin_updated_by = :admin_updated_by,
                resolved_at = :resolved_at,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'status' => $status,
            'admin_notes' => $adminNotes !== null && trim($adminNotes) !== '' ? trim($adminNotes) : null,
            'admin_updated_by' => $adminUserId > 0 ? $adminUserId : null,
            'resolved_at' => in_array($status, ['resolved', 'rejected'], true) ? date('Y-m-d H:i:s') : null,
            'id' => $disputeId,
        ]);
    }
}
