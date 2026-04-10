<?php

declare(strict_types=1);

final class PromoCode
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function allForAdmin(): array
    {
        if (!Database::tableExists('promo_codes')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT
                pc.*,
                COUNT(DISTINCT pcu.user_id) AS targeted_user_count,
                COUNT(DISTINCT pr.id) AS redemption_count
            FROM promo_codes pc
            LEFT JOIN promo_code_users pcu ON pcu.promo_code_id = pc.id
            LEFT JOIN promo_redemptions pr ON pr.promo_code_id = pc.id
            GROUP BY pc.id
            ORDER BY pc.created_at DESC
        ');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findByCode(string $code): ?array
    {
        if (!Database::tableExists('promo_codes')) {
            return null;
        }

        $statement = $this->db->prepare('SELECT * FROM promo_codes WHERE code = :code LIMIT 1');
        $statement->execute(['code' => strtoupper($code)]);
        $promo = $statement->fetch();

        return $promo ?: null;
    }

    public function findById(int $id): ?array
    {
        if (!Database::tableExists('promo_codes')) {
            return null;
        }

        $statement = $this->db->prepare('SELECT * FROM promo_codes WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $promo = $statement->fetch();

        return $promo ?: null;
    }

    public function create(array $data): int
    {
        if (!Database::tableExists('promo_codes') || !Database::tableExists('promo_code_users')) {
            throw new RuntimeException('Promo tables do not exist yet. Run the subscription migration first.');
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare('
                INSERT INTO promo_codes (code, type, amount, max_redemptions, expires_at, active, created_at, updated_at)
                VALUES (:code, :type, :amount, :max_redemptions, :expires_at, :active, NOW(), NOW())
            ');
            $statement->execute([
                'code' => strtoupper($data['code']),
                'type' => $data['type'],
                'amount' => $data['amount'],
                'max_redemptions' => $data['max_redemptions'],
                'expires_at' => $data['expires_at'],
                'active' => $data['active'] ? 1 : 0,
            ]);

            $promoId = (int) $this->db->lastInsertId();
            $this->syncTargetUsers($promoId, $data['target_user_ids'] ?? []);
            $this->db->commit();

            return $promoId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update(int $promoId, array $data): void
    {
        if (!Database::tableExists('promo_codes') || !Database::tableExists('promo_code_users')) {
            throw new RuntimeException('Promo tables do not exist yet. Run the subscription migration first.');
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare('
                UPDATE promo_codes
                SET code = :code,
                    type = :type,
                    amount = :amount,
                    max_redemptions = :max_redemptions,
                    expires_at = :expires_at,
                    active = :active,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $statement->execute([
                'id' => $promoId,
                'code' => strtoupper($data['code']),
                'type' => $data['type'],
                'amount' => $data['amount'],
                'max_redemptions' => $data['max_redemptions'],
                'expires_at' => $data['expires_at'],
                'active' => $data['active'] ? 1 : 0,
            ]);

            $this->syncTargetUsers($promoId, $data['target_user_ids'] ?? []);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function assignedUserIds(int $promoId): array
    {
        if (!Database::tableExists('promo_code_users')) {
            return [];
        }

        $statement = $this->db->prepare('SELECT user_id FROM promo_code_users WHERE promo_code_id = :promo_code_id');
        $statement->execute(['promo_code_id' => $promoId]);

        return array_map(static fn (array $row): int => (int) $row['user_id'], $statement->fetchAll());
    }

    public function validationForUser(string $code, int $userId): ?array
    {
        $promo = $this->findByCode($code);

        if ($promo === null || (int) $promo['active'] !== 1) {
            return null;
        }

        if (!empty($promo['expires_at']) && strtotime((string) $promo['expires_at']) < time()) {
            return null;
        }

        $targetedUsers = $this->assignedUserIds((int) $promo['id']);
        if ($targetedUsers !== [] && !in_array($userId, $targetedUsers, true)) {
            return null;
        }

        if ($this->hasUserRedeemed((int) $promo['id'], $userId)) {
            return null;
        }

        if ($promo['max_redemptions'] !== null && $this->redemptionCount((int) $promo['id']) >= (int) $promo['max_redemptions']) {
            return null;
        }

        return $promo;
    }

    public function applyDiscount(array $promo, int $amountRwf): int
    {
        if ((string) $promo['type'] === 'percent') {
            $discounted = (int) round($amountRwf - (($amountRwf * (int) $promo['amount']) / 100));

            return max(0, $discounted);
        }

        return max(0, $amountRwf - (int) $promo['amount']);
    }

    public function redeem(int $promoId, int $userId): void
    {
        if (!Database::tableExists('promo_redemptions')) {
            throw new RuntimeException('The promo_redemptions table does not exist yet. Run the subscription migration first.');
        }

        $statement = $this->db->prepare('
            INSERT INTO promo_redemptions (promo_code_id, user_id, redeemed_at)
            VALUES (:promo_code_id, :user_id, NOW())
        ');
        $statement->execute([
            'promo_code_id' => $promoId,
            'user_id' => $userId,
        ]);
    }

    private function syncTargetUsers(int $promoId, array $userIds): void
    {
        $normalizedUserIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $userId): int => (int) $userId, $userIds),
            static fn (int $userId): bool => $userId > 0
        )));

        $deleteStatement = $this->db->prepare('DELETE FROM promo_code_users WHERE promo_code_id = :promo_code_id');
        $deleteStatement->execute(['promo_code_id' => $promoId]);

        if ($normalizedUserIds === []) {
            return;
        }

        $insertStatement = $this->db->prepare('
            INSERT INTO promo_code_users (promo_code_id, user_id, created_at)
            VALUES (:promo_code_id, :user_id, NOW())
        ');

        foreach ($normalizedUserIds as $userId) {
            $insertStatement->execute([
                'promo_code_id' => $promoId,
                'user_id' => $userId,
            ]);
        }
    }

    private function hasUserRedeemed(int $promoId, int $userId): bool
    {
        if (!Database::tableExists('promo_redemptions')) {
            return false;
        }

        $statement = $this->db->prepare('
            SELECT id
            FROM promo_redemptions
            WHERE promo_code_id = :promo_code_id
              AND user_id = :user_id
            LIMIT 1
        ');
        $statement->execute([
            'promo_code_id' => $promoId,
            'user_id' => $userId,
        ]);

        return $statement->fetch() !== false;
    }

    private function redemptionCount(int $promoId): int
    {
        if (!Database::tableExists('promo_redemptions')) {
            return 0;
        }

        $statement = $this->db->prepare('SELECT COUNT(*) AS aggregate FROM promo_redemptions WHERE promo_code_id = :promo_code_id');
        $statement->execute(['promo_code_id' => $promoId]);

        return (int) (($statement->fetch()['aggregate'] ?? 0));
    }
}
