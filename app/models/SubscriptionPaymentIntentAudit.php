<?php

declare(strict_types=1);

final class SubscriptionPaymentIntentAudit
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): void
    {
        if (!Database::tableExists('subscription_payment_intent_audit')) {
            return;
        }

        $statement = $this->db->prepare('
            INSERT INTO subscription_payment_intent_audit (
                payment_intent_id,
                actor_user_id,
                actor_type,
                action,
                from_status,
                to_status,
                reason,
                metadata_json,
                created_at
            )
            VALUES (
                :payment_intent_id,
                :actor_user_id,
                :actor_type,
                :action,
                :from_status,
                :to_status,
                :reason,
                :metadata_json,
                NOW()
            )
        ');
        $statement->execute([
            'payment_intent_id' => $data['payment_intent_id'],
            'actor_user_id' => $data['actor_user_id'] ?? null,
            'actor_type' => $data['actor_type'],
            'action' => $data['action'],
            'from_status' => $data['from_status'] ?? null,
            'to_status' => $data['to_status'] ?? null,
            'reason' => $data['reason'] ?? null,
            'metadata_json' => isset($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function countActionForIntentSince(int $paymentIntentId, string $action, string $since): int
    {
        if (!Database::tableExists('subscription_payment_intent_audit')) {
            return 0;
        }

        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM subscription_payment_intent_audit
            WHERE payment_intent_id = :payment_intent_id
              AND action = :action
              AND created_at >= :since
        ');
        $statement->execute([
            'payment_intent_id' => $paymentIntentId,
            'action' => $action,
            'since' => $since,
        ]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function countActionForIntent(int $paymentIntentId, string $action): int
    {
        if (!Database::tableExists('subscription_payment_intent_audit')) {
            return 0;
        }

        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM subscription_payment_intent_audit
            WHERE payment_intent_id = :payment_intent_id
              AND action = :action
        ');
        $statement->execute([
            'payment_intent_id' => $paymentIntentId,
            'action' => $action,
        ]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }
}

