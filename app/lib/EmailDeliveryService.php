<?php

declare(strict_types=1);

final class EmailDeliveryService
{
    private const MAX_ATTEMPTS = 5;

    private EmailOutbox $outbox;
    private EmailDeliveryAttempt $attempts;
    private EmailRecipient $recipients;
    private EmailService $transport;
    private EmailTemplateCatalog $templates;

    public function __construct()
    {
        $this->outbox = new EmailOutbox();
        $this->attempts = new EmailDeliveryAttempt();
        $this->recipients = new EmailRecipient();
        $this->transport = new EmailService();
        $this->templates = new EmailTemplateCatalog();
    }

    public function queue(array $message): ?int
    {
        $recipientEmail = mb_strtolower(trim((string) ($message['recipient_email'] ?? '')));
        $idempotencyKey = trim((string) ($message['idempotency_key'] ?? ''));
        if ($recipientEmail === '' || $idempotencyKey === '') {
            return null;
        }

        $recipient = $this->recipients->ensure($recipientEmail);
        $templateName = trim((string) ($message['template_name'] ?? 'generic_notification'));
        $templateData = is_array($message['template_data'] ?? null) ? $message['template_data'] : [];
        $subject = trim((string) ($message['subject'] ?? ''));
        $missing = $this->templates->validatePayload($templateName, array_merge($templateData, ['subject' => $subject]));

        $status = EmailOutbox::STATUS_PENDING;
        $errorCode = null;
        $errorMessage = null;
        $processedAt = null;
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $status = EmailOutbox::STATUS_FAILED_PERMANENT;
            $errorCode = 'invalid_recipient_email';
            $errorMessage = 'Recipient email is invalid.';
            $processedAt = date('Y-m-d H:i:s');
        } elseif ((string) ($recipient['status'] ?? EmailRecipient::STATUS_ACTIVE) !== EmailRecipient::STATUS_ACTIVE) {
            $status = EmailOutbox::STATUS_SKIPPED;
            $errorCode = 'recipient_suppressed';
            $errorMessage = 'Recipient is marked undeliverable.';
            $processedAt = date('Y-m-d H:i:s');
        } elseif ($missing !== []) {
            $status = EmailOutbox::STATUS_FAILED_PERMANENT;
            $errorCode = 'template_validation_failed';
            $errorMessage = 'Missing template variables: ' . implode(', ', $missing);
            $processedAt = date('Y-m-d H:i:s');
        }

        $created = $this->outbox->create([
            'event_name' => (string) $message['event_name'],
            'entity_type' => (string) ($message['entity_type'] ?? 'event'),
            'entity_id' => (int) ($message['entity_id'] ?? 0),
            'recipient_email' => $recipientEmail,
            'recipient_name' => (string) ($message['recipient_name'] ?? ''),
            'template_name' => $templateName,
            'subject' => $subject,
            'template_data_json' => json_encode($templateData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'idempotency_key' => $idempotencyKey,
            'status' => $status,
            'last_error_code' => $errorCode,
            'last_error_message' => $errorMessage,
            'processed_at' => $processedAt,
            'metadata_json' => json_encode($message['metadata'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return $created['id'];
    }

    public function processDue(int $limit = 50): int
    {
        $processed = 0;

        foreach ($this->outbox->claimDue($limit) as $email) {
            $attemptNumber = ((int) ($email['attempt_count'] ?? 0)) + 1;
            $payload = json_decode((string) ($email['template_data_json'] ?? '{}'), true);
            $payload = is_array($payload) ? $payload : [];
            $payload['subject'] = (string) ($payload['subject'] ?? $email['subject'] ?? '');

            $recipient = $this->recipients->ensure((string) $email['recipient_email']);
            if ((string) ($recipient['status'] ?? EmailRecipient::STATUS_ACTIVE) !== EmailRecipient::STATUS_ACTIVE) {
                $this->outbox->markSkipped((int) $email['id'], 'Recipient is marked undeliverable.');
                $processed++;
                continue;
            }

            try {
                $rendered = $this->templates->render((string) $email['template_name'], $payload);
            } catch (Throwable $exception) {
                $this->attempts->create([
                    'outbox_id' => (int) $email['id'],
                    'attempt_number' => $attemptNumber,
                    'provider' => 'smtp',
                    'request_payload_json' => json_encode(['template_name' => $email['template_name']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'response_payload_json' => null,
                    'result' => 'failed_permanent',
                    'error_code' => 'template_render_failed',
                    'error_message' => $exception->getMessage(),
                ]);
                $this->outbox->markFailedPermanent((int) $email['id'], $attemptNumber, 'template_render_failed', $exception->getMessage());
                $processed++;
                continue;
            }

            $result = $this->transport->sendRenderedEmail([
                'email' => (string) $email['recipient_email'],
                'name' => (string) ($email['recipient_name'] ?? ''),
            ], $rendered['subject'], $rendered['html'], $rendered['text']);

            $attemptResult = ($result['ok'] ?? false) === true ? 'sent' : ((bool) ($result['retryable'] ?? false) ? 'failed_transient' : 'failed_permanent');
            $this->attempts->create([
                'outbox_id' => (int) $email['id'],
                'attempt_number' => $attemptNumber,
                'provider' => 'smtp',
                'request_payload_json' => json_encode([
                    'recipient_email' => $email['recipient_email'],
                    'subject' => $rendered['subject'],
                    'template_name' => $email['template_name'],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'response_payload_json' => json_encode([
                    'provider_message_id' => $result['provider_message_id'] ?? null,
                    'reason' => $result['reason'] ?? null,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'result' => $attemptResult,
                'error_code' => ($result['ok'] ?? false) === true ? null : (string) ($result['reason'] ?? 'smtp_send_failed'),
                'error_message' => ($result['ok'] ?? false) === true ? null : (string) ($result['reason'] ?? 'smtp_send_failed'),
            ]);

            if (($result['ok'] ?? false) === true) {
                $this->outbox->markSent((int) $email['id'], $attemptNumber, $result['provider_message_id'] ?? null);
                $processed++;
                continue;
            }

            $reason = (string) ($result['reason'] ?? 'smtp_send_failed');
            if (!(bool) ($result['retryable'] ?? false)) {
                $this->outbox->markFailedPermanent((int) $email['id'], $attemptNumber, $reason, $reason);
                if ($this->isRecipientFailure($reason)) {
                    $this->recipients->markUndeliverable((string) $email['recipient_email'], $reason);
                }
                $processed++;
                continue;
            }

            if ($attemptNumber >= self::MAX_ATTEMPTS) {
                $this->outbox->markFailedPermanent((int) $email['id'], $attemptNumber, 'max_attempts_exhausted', $reason);
                $processed++;
                continue;
            }

            $this->outbox->scheduleRetry(
                (int) $email['id'],
                $attemptNumber,
                $reason,
                $reason,
                date('Y-m-d H:i:s', time() + $this->backoffSeconds($attemptNumber))
            );
            $processed++;
        }

        return $processed;
    }

    public function metrics(): array
    {
        return $this->outbox->metrics();
    }

    public function recentForAdmin(array $filters = [], int $limit = 100): array
    {
        $rows = $this->outbox->forAdmin($filters, $limit);
        $attempts = $this->attempts->latestForOutboxIds(array_map(static fn (array $row): int => (int) $row['id'], $rows));
        foreach ($rows as &$row) {
            $row['latest_attempt'] = $attempts[(int) $row['id']] ?? null;
        }
        unset($row);

        return $rows;
    }

    public function resend(int $outboxId, int $adminUserId): ?int
    {
        return $this->outbox->createResend($outboxId, $adminUserId);
    }

    private function backoffSeconds(int $attemptNumber): int
    {
        return match ($attemptNumber) {
            1 => 300,
            2 => 900,
            3 => 3600,
            default => 21600,
        };
    }

    private function isRecipientFailure(string $reason): bool
    {
        $normalized = strtolower($reason);
        foreach (['invalid_recipient_email', 'user unknown', 'mailbox unavailable', 'recipient address rejected', 'no such user', 'unknown user'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
