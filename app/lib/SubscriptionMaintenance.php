<?php

declare(strict_types=1);

final class SubscriptionMaintenance
{
    private PDO $db;
    private SubscriptionNotification $notifications;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->notifications = new SubscriptionNotification();
    }

    public function markExpiredActiveAsPastDueAndQueueReminders(): array
    {
        if (!Database::tableExists('subscriptions') || !Database::tableExists('plans')) {
            return ['past_due_marked' => 0, 'reminders_queued' => 0];
        }

        $statement = $this->db->prepare('
            SELECT
                s.id,
                s.user_id,
                s.current_period_ends_at,
                u.email,
                p.full_name,
                plans.name AS plan_name
            FROM subscriptions s
            INNER JOIN users u ON u.id = s.user_id
            INNER JOIN profiles p ON p.user_id = u.id
            INNER JOIN plans ON plans.id = COALESCE(s.active_plan_id, s.plan_id)
            WHERE s.status = :status
              AND s.current_period_ends_at IS NOT NULL
              AND s.current_period_ends_at < NOW()
        ');
        $statement->execute(['status' => 'active']);
        $expired = $statement->fetchAll();

        $pastDueMarked = 0;
        $remindersQueued = 0;

        foreach ($expired as $subscription) {
            $updateStatement = $this->db->prepare('
                UPDATE subscriptions
                SET status = :status, updated_at = NOW()
                WHERE id = :id AND status = :current_status
            ');
            $updateStatement->execute([
                'id' => $subscription['id'],
                'status' => 'past_due',
                'current_status' => 'active',
            ]);

            if ($updateStatement->rowCount() > 0) {
                $pastDueMarked++;
            }

            $referenceKey = 'past_due:' . (string) $subscription['current_period_ends_at'];
            $queued = $this->notifications->queuePastDueReminder((int) $subscription['user_id'], $referenceKey, [
                'email' => (string) $subscription['email'],
                'full_name' => (string) $subscription['full_name'],
                'plan_name' => (string) $subscription['plan_name'],
                'current_period_ends_at' => (string) $subscription['current_period_ends_at'],
                'grace_days' => SubscriptionConfig::graceDays(),
            ]);

            if ($queued) {
                $remindersQueued++;
            }
        }

        return [
            'past_due_marked' => $pastDueMarked,
            'reminders_queued' => $remindersQueued,
        ];
    }

    public function deliverQueuedReminderStubs(int $limit = 50): int
    {
        $queued = $this->notifications->queued($limit);
        $delivered = 0;

        foreach ($queued as $notification) {
            $payload = json_decode((string) ($notification['payload_json'] ?? '{}'), true);
            $payload = is_array($payload) ? $payload : [];
            LeadCapture::append('subscription_reminders', array_merge($payload, [
                'notification_id' => (int) $notification['id'],
                'sent_at' => date(DATE_ATOM),
            ]));
            $this->notifications->markSent((int) $notification['id']);
            $delivered++;
        }

        return $delivered;
    }
}
