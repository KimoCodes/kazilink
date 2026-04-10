<?php

declare(strict_types=1);

final class SubscriptionMaintenance
{
    private PDO $db;
    private SubscriptionNotification $notifications;
    private Subscription $subscriptions;
    private SubscriptionPaymentIntent $paymentIntents;
    private SubscriptionPaymentIntentAudit $paymentIntentAudit;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->notifications = new SubscriptionNotification();
        $this->subscriptions = new Subscription();
        $this->paymentIntents = new SubscriptionPaymentIntent();
        $this->paymentIntentAudit = new SubscriptionPaymentIntentAudit();
        $this->notificationService = new NotificationService();
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

    public function activateApprovedManualPayments(int $limit = 100): array
    {
        $now = date('Y-m-d H:i:s');
        $activated = 0;
        $expired = $this->paymentIntents->expireStale($now);

        foreach ($this->paymentIntents->dueForActivation($now, $limit) as $intent) {
            try {
                $this->subscriptions->markLatestPaymentPendingForUser(
                    (int) $intent['user_id'],
                    (int) $intent['plan_id'],
                    (string) $intent['reference']
                );
                $this->subscriptions->activateFromVerifiedPayment(
                    (int) $intent['user_id'],
                    (int) $intent['plan_id'],
                    (string) $intent['reference'],
                    (string) $intent['intended_activation_at']
                );
                $this->paymentIntents->markActivated((int) $intent['id'], $now);
                $this->paymentIntentAudit->create([
                    'payment_intent_id' => (int) $intent['id'],
                    'actor_user_id' => null,
                    'actor_type' => 'system',
                    'action' => 'activated',
                    'from_status' => (string) $intent['status'],
                    'to_status' => SubscriptionPaymentIntent::STATUS_ACTIVATED,
                    'reason' => 'Activated after approved payment reached its scheduled activation time.',
                ]);
                $activated++;
            } catch (Throwable $exception) {
                $this->paymentIntentAudit->create([
                    'payment_intent_id' => (int) $intent['id'],
                    'actor_user_id' => null,
                    'actor_type' => 'system',
                    'action' => 'activation_failed',
                    'from_status' => (string) $intent['status'],
                    'to_status' => (string) $intent['status'],
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'manual_payments_activated' => $activated,
            'manual_payments_expired' => $expired,
        ];
    }

    public function emitExpiringSoonPaymentNotifications(int $limit = 100): int
    {
        $windowStart = date('Y-m-d H:i:s');
        $windowEnd = date('Y-m-d H:i:s', strtotime('+' . max(1, (int) app_config('notifications.expiring_soon_hours', 24)) . ' hours'));
        $emitted = 0;

        foreach ($this->paymentIntents->expiringSoonCandidates($windowStart, $windowEnd, $limit) as $intent) {
            $eventId = $this->notificationService->emit(
                'payment_expiring_soon',
                sprintf('payment_expiring_soon:%d:%s', (int) $intent['id'], (string) $intent['deadline_at']),
                [
                    'payment_intent_id' => (int) $intent['id'],
                    'entity_type' => 'payment_intent',
                    'entity_id' => (int) $intent['id'],
                    'user_id' => (int) $intent['user_id'],
                    'user_email' => (string) ($intent['email'] ?? ''),
                    'user_name' => (string) (($intent['full_name'] ?? '') !== '' ? $intent['full_name'] : ($intent['email'] ?? 'User')),
                    'plan_id' => (int) $intent['plan_id'],
                    'plan_name' => (string) ($intent['plan_name'] ?? 'Plan'),
                    'amount_expected_rwf' => (int) ($intent['amount_expected_rwf'] ?? 0),
                    'currency' => 'RWF',
                    'status' => (string) ($intent['status'] ?? SubscriptionPaymentIntent::STATUS_PENDING_VERIFICATION),
                    'submitted_at' => (string) ($intent['submitted_at'] ?? ''),
                    'deadline_at' => (string) ($intent['deadline_at'] ?? ''),
                    'intended_activation_at' => (string) ($intent['intended_activation_at'] ?? ''),
                    'payment_link' => url_for('subscriptions/index', ['intent' => (int) $intent['id']]),
                    'admin_review_link' => url_for('admin/subscriptions'),
                ]
            );

            if ($eventId !== null) {
                $emitted++;
            }
        }

        return $emitted;
    }

    public function processNotificationOutbox(int $limit = 100): int
    {
        return $this->notificationService->processOutbox($limit);
    }

    public function deliverQueuedNotificationEmails(int $limit = 50): int
    {
        return $this->notificationService->deliverQueuedEmails($limit);
    }
}
