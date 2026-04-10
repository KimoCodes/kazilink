<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$db = Database::connection();
$requiredTables = [
    'email_outbox',
    'email_recipients',
    'email_delivery_attempts',
];

foreach ($requiredTables as $table) {
    test_assert(Database::tableExists($table), 'Missing required table for tests: ' . $table);
}

$db->beginTransaction();

try {
    $service = new EmailDeliveryService();
    $recipients = new EmailRecipient();
    $outbox = new EmailOutbox();

    $firstId = $service->queue([
        'event_name' => 'ops_notice',
        'entity_type' => 'system_notice',
        'entity_id' => 42,
        'recipient_email' => 'ops@example.com',
        'recipient_name' => 'Ops',
        'template_name' => 'generic_notification',
        'subject' => 'Queue once',
        'template_data' => [
            'subject' => 'Queue once',
            'message' => 'The queue should only store this once.',
            'platform_name' => 'Kazilink',
        ],
        'idempotency_key' => 'ops_notice:system_notice:42:ops@example.com',
    ]);
    $secondId = $service->queue([
        'event_name' => 'ops_notice',
        'entity_type' => 'system_notice',
        'entity_id' => 42,
        'recipient_email' => 'ops@example.com',
        'recipient_name' => 'Ops',
        'template_name' => 'generic_notification',
        'subject' => 'Queue once',
        'template_data' => [
            'subject' => 'Queue once',
            'message' => 'The queue should only store this once.',
            'platform_name' => 'Kazilink',
        ],
        'idempotency_key' => 'ops_notice:system_notice:42:ops@example.com',
    ]);
    test_assert($firstId !== null && $secondId === $firstId, 'Duplicate idempotency keys should resolve to the same outbox record.');

    $invalidId = $service->queue([
        'event_name' => 'ops_notice_invalid',
        'entity_type' => 'system_notice',
        'entity_id' => 43,
        'recipient_email' => 'not-an-email',
        'recipient_name' => 'Broken',
        'template_name' => 'generic_notification',
        'subject' => 'Broken recipient',
        'template_data' => [
            'subject' => 'Broken recipient',
            'message' => 'This should fail fast.',
            'platform_name' => 'Kazilink',
        ],
        'idempotency_key' => 'ops_notice_invalid:system_notice:43:not-an-email',
    ]);
    $invalid = $invalidId !== null ? $outbox->findById($invalidId) : null;
    test_assert(is_array($invalid) && (string) $invalid['status'] === EmailOutbox::STATUS_FAILED_PERMANENT, 'Invalid recipients should be marked failed permanently at queue time.');

    $recipients->markUndeliverable('suppressed@example.com', 'recipient address rejected');
    $suppressedId = $service->queue([
        'event_name' => 'ops_notice_suppressed',
        'entity_type' => 'system_notice',
        'entity_id' => 44,
        'recipient_email' => 'suppressed@example.com',
        'recipient_name' => 'Suppressed',
        'template_name' => 'generic_notification',
        'subject' => 'Suppressed',
        'template_data' => [
            'subject' => 'Suppressed',
            'message' => 'This should be skipped.',
            'platform_name' => 'Kazilink',
        ],
        'idempotency_key' => 'ops_notice_suppressed:system_notice:44:suppressed@example.com',
    ]);
    $suppressed = $suppressedId !== null ? $outbox->findById($suppressedId) : null;
    test_assert(is_array($suppressed) && (string) $suppressed['status'] === EmailOutbox::STATUS_SKIPPED, 'Suppressed recipients should be skipped without retry.');

    $renderFailureId = $service->queue([
        'event_name' => 'ops_notice_render_failure',
        'entity_type' => 'system_notice',
        'entity_id' => 45,
        'recipient_email' => 'render@example.com',
        'recipient_name' => 'Render',
        'template_name' => 'generic_notification',
        'subject' => 'Missing message',
        'template_data' => [
            'subject' => 'Missing message',
            'platform_name' => 'Kazilink',
        ],
        'idempotency_key' => 'ops_notice_render_failure:system_notice:45:render@example.com',
    ]);
    $renderFailure = $renderFailureId !== null ? $outbox->findById($renderFailureId) : null;
    test_assert(is_array($renderFailure) && (string) $renderFailure['status'] === EmailOutbox::STATUS_FAILED_PERMANENT, 'Missing required template variables should fail permanently at queue time.');

    $resendId = $service->resend($invalidId ?? 0, 1);
    $resend = $resendId !== null ? $outbox->findById($resendId) : null;
    test_assert(is_array($resend) && (string) $resend['status'] === EmailOutbox::STATUS_PENDING, 'Safe resend should create a new pending outbox row.');

    echo "PASS email delivery reliability\n";
} catch (Throwable $exception) {
    echo 'FAIL ' . $exception->getMessage() . PHP_EOL;
    $db->rollBack();
    exit(1);
}

$db->rollBack();
exit(0);
