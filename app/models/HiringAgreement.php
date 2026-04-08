<?php

declare(strict_types=1);

final class HiringAgreement
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function createDraftForBooking(int $bookingId): int
    {
        $booking = $this->fetchBookingAgreementPayload($bookingId);

        if ($booking === null) {
            throw new RuntimeException('Booking not found for agreement generation.');
        }

        $existing = $this->findByBookingId($bookingId);

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $agreementUid = $this->generateAgreementUid();
        $agreedAmount = (float) ($booking['agreed_amount'] ?? $booking['budget'] ?? 0);
        $callOutFee = max(2000, (int) round($agreedAmount * 0.2));
        $locationText = $this->locationText($booking);

        $statement = $this->db->prepare('
            INSERT INTO hiring_agreements (
                agreement_uid,
                booking_id,
                task_id,
                client_user_id,
                tasker_user_id,
                job_title,
                job_description,
                category,
                location_text,
                start_datetime,
                expected_duration,
                offline_payment_terms_text,
                compensation_terms_text,
                cancellation_terms_text,
                dispute_window_hours,
                status,
                created_at,
                updated_at
            ) VALUES (
                :agreement_uid,
                :booking_id,
                :task_id,
                :client_user_id,
                :tasker_user_id,
                :job_title,
                :job_description,
                :category,
                :location_text,
                :start_datetime,
                :expected_duration,
                :offline_payment_terms_text,
                :compensation_terms_text,
                :cancellation_terms_text,
                :dispute_window_hours,
                :status,
                NOW(),
                NOW()
            )
        ');

        $statement->execute([
            'agreement_uid' => $agreementUid,
            'booking_id' => $bookingId,
            'task_id' => (int) $booking['task_id'],
            'client_user_id' => (int) $booking['client_id'],
            'tasker_user_id' => (int) $booking['tasker_id'],
            'job_title' => (string) $booking['title'],
            'job_description' => (string) $booking['description'],
            'category' => (string) $booking['category_name'],
            'location_text' => $locationText,
            'start_datetime' => $booking['scheduled_for'] ?: $booking['booked_at'],
            'expected_duration' => 'To be confirmed in platform messages before work starts.',
            'offline_payment_terms_text' => sprintf(
                'Payment for this hire is arranged directly between the client and tasker offline. %s does not process, collect, store, or guarantee any card, bank, mobile-money, or cash payment.',
                app_config('name')
            ),
            'compensation_terms_text' => sprintf(
                'If the client is unavailable on arrival, cannot provide access, or the work site is closed, the client should pay documented transport costs plus a standby/call-out fee of at least %s. If the scope changes materially, both parties should confirm the revised scope and compensation in platform messages before continuing.',
                moneyRwf($callOutFee)
            ),
            'cancellation_terms_text' => 'If the tasker does not arrive within 30 minutes of the agreed start time and does not communicate through the platform, the client may cancel the hire. If either side needs to cancel or reschedule, they should record it in platform messages so the timeline stays auditable.',
            'dispute_window_hours' => 48,
            'status' => 'pending_acceptance',
        ]);

        $agreementId = (int) $this->db->lastInsertId();

        $this->logEvent($agreementId, (int) $booking['client_id'], 'agreement_created', [
            'booking_id' => $bookingId,
            'task_id' => (int) $booking['task_id'],
            'agreement_uid' => $agreementUid,
            'generated_from' => 'booking_confirmation',
        ]);

        return $agreementId;
    }

    public function findByBookingId(int $bookingId): ?array
    {
        $statement = $this->db->prepare('
            SELECT *
            FROM hiring_agreements
            WHERE booking_id = :booking_id
            LIMIT 1
        ');
        $statement->execute(['booking_id' => $bookingId]);
        $agreement = $statement->fetch();

        return $agreement ?: null;
    }

    public function findVisibleByBookingId(int $bookingId, int $userId, string $role): ?array
    {
        $agreement = $this->findByBookingId($bookingId);

        if ($agreement === null) {
            return null;
        }

        return $this->findVisibleById((int) $agreement['id'], $userId, $role);
    }

    public function findVisibleByBookingIds(array $bookingIds, int $userId, string $role): array
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $bookingId): int => (int) $bookingId, $bookingIds),
            static fn (int $bookingId): bool => $bookingId > 0
        )));

        if ($normalizedIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach ($normalizedIds as $index => $bookingId) {
            $placeholder = 'booking_id_' . $index;
            $placeholders[] = ':' . $placeholder;
            $params[$placeholder] = $bookingId;
        }

        $sql = '
            SELECT
                ha.*,
                b.status AS booking_status,
                b.booked_at,
                b.completed_at,
                bid.amount AS agreed_amount,
                t.scheduled_for,
                client_profile.full_name AS client_name,
                client_profile.phone AS client_phone,
                client_user.email AS client_email,
                tasker_profile.full_name AS tasker_name,
                tasker_profile.phone AS tasker_phone,
                tasker_user.email AS tasker_email
            FROM hiring_agreements ha
            INNER JOIN bookings b ON b.id = ha.booking_id
            INNER JOIN bids bid ON bid.id = b.bid_id
            INNER JOIN tasks t ON t.id = ha.task_id
            INNER JOIN users client_user ON client_user.id = ha.client_user_id
            LEFT JOIN profiles client_profile ON client_profile.user_id = client_user.id
            INNER JOIN users tasker_user ON tasker_user.id = ha.tasker_user_id
            LEFT JOIN profiles tasker_profile ON tasker_profile.user_id = tasker_user.id
            WHERE ha.booking_id IN (' . implode(', ', $placeholders) . ')
        ';

        if ($role !== 'admin') {
            $sql .= ' AND (ha.client_user_id = :client_user_id OR ha.tasker_user_id = :tasker_user_id)';
            $params['client_user_id'] = $userId;
            $params['tasker_user_id'] = $userId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        $agreements = [];

        foreach ($statement->fetchAll() as $agreement) {
            $agreements[(int) $agreement['booking_id']] = $agreement;
        }

        return $agreements;
    }

    public function findVisibleById(int $agreementId, int $userId, string $role): ?array
    {
        $params = ['id' => $agreementId];
        $sql = '
            SELECT
                ha.*,
                b.status AS booking_status,
                b.booked_at,
                b.completed_at,
                bid.amount AS agreed_amount,
                t.scheduled_for,
                client_profile.full_name AS client_name,
                client_profile.phone AS client_phone,
                client_user.email AS client_email,
                tasker_profile.full_name AS tasker_name,
                tasker_profile.phone AS tasker_phone,
                tasker_user.email AS tasker_email
            FROM hiring_agreements ha
            INNER JOIN bookings b ON b.id = ha.booking_id
            INNER JOIN bids bid ON bid.id = b.bid_id
            INNER JOIN tasks t ON t.id = ha.task_id
            INNER JOIN users client_user ON client_user.id = ha.client_user_id
            LEFT JOIN profiles client_profile ON client_profile.user_id = client_user.id
            INNER JOIN users tasker_user ON tasker_user.id = ha.tasker_user_id
            LEFT JOIN profiles tasker_profile ON tasker_profile.user_id = tasker_user.id
            WHERE ha.id = :id
        ';

        if ($role !== 'admin') {
            $sql .= ' AND (ha.client_user_id = :client_user_id OR ha.tasker_user_id = :tasker_user_id)';
            $params['client_user_id'] = $userId;
            $params['tasker_user_id'] = $userId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $agreement = $statement->fetch();

        return $agreement ?: null;
    }

    public function findPublicByUid(string $agreementUid): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                agreement_uid,
                job_title,
                category,
                location_text,
                start_datetime,
                status,
                created_at
            FROM hiring_agreements
            WHERE agreement_uid = :agreement_uid
            LIMIT 1
        ');
        $statement->execute(['agreement_uid' => $agreementUid]);
        $agreement = $statement->fetch();

        return $agreement ?: null;
    }

    public function accept(int $agreementId, int $actorUserId, array $signatureMeta): void
    {
        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare('
                SELECT *
                FROM hiring_agreements
                WHERE id = :id
                FOR UPDATE
            ');
            $statement->execute(['id' => $agreementId]);
            $agreement = $statement->fetch();

            if (!$agreement) {
                throw new RuntimeException('Agreement not found.');
            }

            if (!in_array((string) $agreement['status'], ['draft', 'pending_acceptance', 'accepted'], true)) {
                throw new RuntimeException('This agreement cannot be accepted in its current state.');
            }

            $field = null;

            if ((int) $agreement['client_user_id'] === $actorUserId) {
                $field = 'client_accepted_at';
            } elseif ((int) $agreement['tasker_user_id'] === $actorUserId) {
                $field = 'tasker_accepted_at';
            }

            if ($field === null) {
                throw new RuntimeException('You are not allowed to accept this agreement.');
            }

            if (!empty($agreement[$field])) {
                throw new RuntimeException('You have already accepted this agreement.');
            }

            $update = $this->db->prepare("
                UPDATE hiring_agreements
                SET {$field} = NOW(), updated_at = NOW()
                WHERE id = :id
            ");
            $update->execute(['id' => $agreementId]);

            $statement->execute(['id' => $agreementId]);
            $updatedAgreement = $statement->fetch();

            if (!$updatedAgreement) {
                throw new RuntimeException('Agreement not found.');
            }

            $status = (!empty($updatedAgreement['client_accepted_at']) && !empty($updatedAgreement['tasker_accepted_at']))
                ? 'accepted'
                : 'pending_acceptance';

            $statusUpdate = $this->db->prepare('
                UPDATE hiring_agreements
                SET status = :status, updated_at = NOW()
                WHERE id = :id
            ');
            $statusUpdate->execute([
                'status' => $status,
                'id' => $agreementId,
            ]);

            $this->logEvent($agreementId, $actorUserId, 'agreement_accepted', [
                'accepted_by' => $field === 'client_accepted_at' ? 'client' : 'tasker',
                'ip_address' => $signatureMeta['ip_address'] ?? 'unknown',
                'user_agent' => $signatureMeta['user_agent'] ?? 'unknown',
                'confirmed_offline_payment' => true,
                'confirmed_scope_and_disputes' => true,
                'accepted_at' => date('Y-m-d H:i:s'),
            ]);

            if ($status === 'accepted') {
                $this->logEvent($agreementId, $actorUserId, 'agreement_fully_executed', [
                    'accepted_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function markDisputed(int $agreementId): void
    {
        $statement = $this->db->prepare('
            UPDATE hiring_agreements
            SET status = :status, updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'status' => 'disputed',
            'id' => $agreementId,
        ]);
    }

    public function eventsForAgreement(int $agreementId, int $limit = 25): array
    {
        $statement = $this->db->prepare('
            SELECT
                ae.*,
                p.full_name AS actor_name,
                u.email AS actor_email
            FROM agreement_events ae
            LEFT JOIN users u ON u.id = ae.actor_user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE ae.agreement_id = :agreement_id
            ORDER BY ae.created_at DESC, ae.id DESC
            LIMIT :limit
        ');
        $statement->bindValue(':agreement_id', $agreementId, PDO::PARAM_INT);
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function logEvent(int $agreementId, ?int $actorUserId, string $eventType, array $eventPayload = []): void
    {
        $statement = $this->db->prepare('
            INSERT INTO agreement_events (agreement_id, actor_user_id, event_type, event_json, created_at)
            VALUES (:agreement_id, :actor_user_id, :event_type, :event_json, NOW())
        ');

        $json = json_encode($eventPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $statement->execute([
            'agreement_id' => $agreementId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'event_json' => $json !== false ? $json : null,
        ]);
    }

    public function countByStatus(string $status): int
    {
        $statement = $this->db->prepare('
            SELECT COUNT(*) AS aggregate
            FROM hiring_agreements
            WHERE status = :status
        ');
        $statement->execute(['status' => $status]);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function latestForAdmin(int $limit = 50): array
    {
        $statement = $this->db->prepare('
            SELECT
                ha.*,
                client_profile.full_name AS client_name,
                tasker_profile.full_name AS tasker_name
            FROM hiring_agreements ha
            LEFT JOIN profiles client_profile ON client_profile.user_id = ha.client_user_id
            LEFT JOIN profiles tasker_profile ON tasker_profile.user_id = ha.tasker_user_id
            ORDER BY ha.updated_at DESC, ha.id DESC
            LIMIT :limit
        ');
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function fetchBookingAgreementPayload(int $bookingId): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                b.id,
                b.task_id,
                b.client_id,
                b.tasker_id,
                b.booked_at,
                bid.amount AS agreed_amount,
                t.title,
                t.description,
                t.city,
                t.region,
                t.country,
                t.budget,
                t.scheduled_for,
                c.name AS category_name
            FROM bookings b
            INNER JOIN bids bid ON bid.id = b.bid_id
            INNER JOIN tasks t ON t.id = b.task_id
            INNER JOIN categories c ON c.id = t.category_id
            WHERE b.id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $bookingId]);
        $booking = $statement->fetch();

        return $booking ?: null;
    }

    private function locationText(array $payload): string
    {
        $parts = array_values(array_filter([
            normalize_whitespace((string) ($payload['city'] ?? '')),
            normalize_whitespace((string) ($payload['region'] ?? '')),
            normalize_whitespace((string) ($payload['country'] ?? '')),
        ], static fn (string $value): bool => $value !== ''));

        return implode(', ', $parts);
    }

    private function generateAgreementUid(): string
    {
        $prefix = 'KZ-' . date('Ymd') . '-';

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = $prefix . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $statement = $this->db->prepare('SELECT id FROM hiring_agreements WHERE agreement_uid = :agreement_uid LIMIT 1');
            $statement->execute(['agreement_uid' => $candidate]);

            if (!$statement->fetch()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to generate a unique agreement identifier.');
    }
}
