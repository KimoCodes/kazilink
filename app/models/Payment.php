<?php

declare(strict_types=1);

final class Payment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createFromCheckoutSession(array $session, ?int $userId = null): void
    {
        $this->upsertFromCheckoutSession($session, null, $userId);
    }

    public function upsertFromCheckoutSession(array $session, ?string $eventType = null, ?int $fallbackUserId = null, ?string $eventId = null): void
    {
        $bookingId = (int) ($session['metadata']['booking_id'] ?? 0);
        $taskId = (int) ($session['metadata']['task_id'] ?? 0);
        $planId = (string) ($session['metadata']['plan_id'] ?? $session['client_reference_id'] ?? 'custom');
        $planName = (string) ($session['metadata']['plan_name'] ?? 'Payment');
        $amountMinor = (int) ($session['amount_total'] ?? 0);
        $currency = strtolower((string) ($session['currency'] ?? app_config('stripe.currency', 'rwf')));
        $checkoutStatus = (string) ($session['status'] ?? '');
        $stripePaymentStatus = (string) ($session['payment_status'] ?? '');
        $paymentIntentId = $session['payment_intent'] ?? null;
        $paymentIntentId = is_string($paymentIntentId) ? $paymentIntentId : null;
        $stripeCustomerId = $session['customer'] ?? null;
        $stripeCustomerId = is_string($stripeCustomerId) ? $stripeCustomerId : null;
        $customerEmail = (string) ($session['customer_details']['email'] ?? $session['customer_email'] ?? '');
        $userId = $this->resolveUserId($session, $fallbackUserId);
        $status = $this->mapStatus($checkoutStatus, $stripePaymentStatus, $eventType);
        $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;
        $payloadJson = json_encode($session, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $statement = $this->db->prepare('
            INSERT INTO payments (
                user_id,
                booking_id,
                task_id,
                plan_id,
                plan_name,
                amount_minor,
                currency,
                status,
                checkout_status,
                stripe_payment_status,
                checkout_session_id,
                payment_intent_id,
                stripe_customer_id,
                customer_email,
                last_event_id,
                last_event_type,
                metadata_json,
                paid_at,
                created_at,
                updated_at
            ) VALUES (
                :user_id,
                :booking_id,
                :task_id,
                :plan_id,
                :plan_name,
                :amount_minor,
                :currency,
                :status,
                :checkout_status,
                :stripe_payment_status,
                :checkout_session_id,
                :payment_intent_id,
                :stripe_customer_id,
                :customer_email,
                :last_event_id,
                :last_event_type,
                :metadata_json,
                :paid_at,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                user_id = COALESCE(VALUES(user_id), user_id),
                booking_id = COALESCE(VALUES(booking_id), booking_id),
                task_id = COALESCE(VALUES(task_id), task_id),
                plan_id = VALUES(plan_id),
                plan_name = VALUES(plan_name),
                amount_minor = VALUES(amount_minor),
                currency = VALUES(currency),
                status = VALUES(status),
                checkout_status = VALUES(checkout_status),
                stripe_payment_status = VALUES(stripe_payment_status),
                payment_intent_id = COALESCE(VALUES(payment_intent_id), payment_intent_id),
                stripe_customer_id = COALESCE(VALUES(stripe_customer_id), stripe_customer_id),
                customer_email = COALESCE(NULLIF(VALUES(customer_email), \'\'), customer_email),
                last_event_id = COALESCE(VALUES(last_event_id), last_event_id),
                last_event_type = COALESCE(VALUES(last_event_type), last_event_type),
                metadata_json = VALUES(metadata_json),
                paid_at = COALESCE(VALUES(paid_at), paid_at),
                updated_at = NOW()
        ');

        $statement->execute([
            'user_id' => $userId,
            'booking_id' => $bookingId > 0 ? $bookingId : null,
            'task_id' => $taskId > 0 ? $taskId : null,
            'plan_id' => $planId,
            'plan_name' => $planName,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'status' => $status,
            'checkout_status' => $checkoutStatus !== '' ? $checkoutStatus : null,
            'stripe_payment_status' => $stripePaymentStatus !== '' ? $stripePaymentStatus : null,
            'checkout_session_id' => (string) ($session['id'] ?? ''),
            'payment_intent_id' => $paymentIntentId,
            'stripe_customer_id' => $stripeCustomerId,
            'customer_email' => $customerEmail !== '' ? $customerEmail : null,
            'last_event_id' => $eventId,
            'last_event_type' => $eventType,
            'metadata_json' => $payloadJson !== false ? $payloadJson : null,
            'paid_at' => $paidAt,
        ]);
    }

    public function findByCheckoutSessionId(string $sessionId): ?array
    {
        $statement = $this->db->prepare('
            SELECT p.*, prof.full_name
            FROM payments p
            LEFT JOIN profiles prof ON prof.user_id = p.user_id
            WHERE p.checkout_session_id = :session_id
            LIMIT 1
        ');
        $statement->execute(['session_id' => $sessionId]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function findLatestForBooking(int $bookingId): ?array
    {
        $statement = $this->db->prepare('
            SELECT p.*, prof.full_name
            FROM payments p
            LEFT JOIN profiles prof ON prof.user_id = p.user_id
            WHERE p.booking_id = :booking_id
            ORDER BY COALESCE(p.paid_at, p.updated_at, p.created_at) DESC, p.id DESC
            LIMIT 1
        ');
        $statement->execute(['booking_id' => $bookingId]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function latestByBookingIds(array $bookingIds): array
    {
        $bookingIds = array_values(array_unique(array_filter(array_map('intval', $bookingIds), static fn (int $id): bool => $id > 0)));

        if ($bookingIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($bookingIds), '?'));
        $statement = $this->db->prepare("
            SELECT p.*
            FROM payments p
            WHERE p.booking_id IN ($placeholders)
            ORDER BY COALESCE(p.paid_at, p.updated_at, p.created_at) DESC, p.id DESC
        ");
        $statement->execute($bookingIds);

        $grouped = [];

        foreach ($statement->fetchAll() as $payment) {
            $bookingId = (int) ($payment['booking_id'] ?? 0);

            if ($bookingId > 0 && !isset($grouped[$bookingId])) {
                $grouped[$bookingId] = $payment;
            }
        }

        return $grouped;
    }

    public function latest(int $limit = 20): array
    {
        $statement = $this->db->prepare('
            SELECT p.*, prof.full_name
            FROM payments p
            LEFT JOIN profiles prof ON prof.user_id = p.user_id
            ORDER BY
                COALESCE(p.paid_at, p.updated_at, p.created_at) DESC,
                p.id DESC
            LIMIT :limit
        ');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countPaid(): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) AS aggregate FROM payments WHERE status = :status');
        $statement->execute(['status' => 'paid']);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function totalPaidMinor(): int
    {
        $statement = $this->db->prepare('SELECT COALESCE(SUM(amount_minor), 0) AS aggregate FROM payments WHERE status = :status');
        $statement->execute(['status' => 'paid']);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    private function resolveUserId(array $session, ?int $fallbackUserId): ?int
    {
        $metadataUserId = (int) ($session['metadata']['user_id'] ?? 0);

        if ($metadataUserId > 0) {
            return $metadataUserId;
        }

        return $fallbackUserId !== null && $fallbackUserId > 0 ? $fallbackUserId : null;
    }

    private function mapStatus(string $checkoutStatus, string $stripePaymentStatus, ?string $eventType): string
    {
        if ($eventType === 'checkout.session.async_payment_failed') {
            return 'failed';
        }

        if ($eventType === 'checkout.session.expired' || $checkoutStatus === 'expired') {
            return 'expired';
        }

        if ($stripePaymentStatus === 'paid' || $eventType === 'checkout.session.async_payment_succeeded') {
            return 'paid';
        }

        if ($stripePaymentStatus === 'unpaid' && $checkoutStatus === 'complete') {
            return 'pending';
        }

        if ($stripePaymentStatus === 'no_payment_required') {
            return 'paid';
        }

        return 'pending';
    }
}
