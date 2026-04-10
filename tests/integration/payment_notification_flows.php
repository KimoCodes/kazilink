<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$db = Database::connection();
$requiredTables = [
    'users',
    'profiles',
    'plans',
    'subscription_payment_intents',
    'notification_events_outbox',
    'notifications',
    'email_outbox',
];

foreach ($requiredTables as $table) {
    test_assert(Database::tableExists($table), 'Missing required table for tests: ' . $table);
}

$db->beginTransaction();

try {
    $service = new NotificationService();
    $maintenance = new SubscriptionMaintenance();
    $paymentIntentModel = new SubscriptionPaymentIntent();

    $adminId = test_create_user($db, 'admin', 'notify_admin_' . bin2hex(random_bytes(3)) . '@example.com', 'Notify Admin');
    $userId = test_create_user($db, 'client', 'notify_user_' . bin2hex(random_bytes(3)) . '@example.com', 'Notify User');
    $activeAdminCount = (int) $db->query("SELECT COUNT(*) AS aggregate FROM users WHERE role = 'admin' AND is_active = 1")->fetch()['aggregate'];

    $planId = (new Plan())->create([
        'slug' => 'notify-plan-' . bin2hex(random_bytes(3)),
        'name' => 'Notify Plan',
        'price_rwf' => 1500,
        'visibility_level' => 2,
        'max_applications_per_day' => 10,
        'priority_level' => 2,
        'job_alert_delay_minutes' => 5,
        'max_active_jobs' => 2,
        'commission_discount' => 0,
        'badge_name' => '',
        'active' => true,
    ]);

    $payload = [
        'payment_intent_id' => 999,
        'user_id' => $userId,
        'user_email' => 'notify_user@example.com',
        'user_name' => 'Notify User',
        'plan_id' => $planId,
        'plan_name' => 'Notify Plan',
        'amount_expected_rwf' => 1500,
        'currency' => 'RWF',
        'status' => SubscriptionPaymentIntent::STATUS_PENDING_VERIFICATION,
        'submitted_at' => date('Y-m-d H:i:s'),
        'deadline_at' => date('Y-m-d H:i:s', strtotime('+20 hours')),
        'intended_activation_at' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'payment_link' => '/informal/?route=subscriptions%2Findex&intent=999',
        'admin_review_link' => '/informal/?route=admin%2Fsubscriptions',
    ];

    $created = $service->emit('payment_submitted', 'payment_submitted:999:' . $payload['submitted_at'], $payload);
    test_assert($created !== null, 'Payment submitted event should be written to the outbox.');
    $service->processOutbox();

    $notifications = $db->query('SELECT recipient_type, recipient_id, channel, event_name FROM notifications ORDER BY id ASC')->fetchAll();
    $emails = $db->query('SELECT recipient_email, event_name FROM email_outbox ORDER BY id ASC')->fetchAll();
    $submittedAdmin = array_values(array_filter($notifications, static fn (array $row): bool => $row['event_name'] === 'payment_submitted' && $row['recipient_type'] === 'admin'));
    $submittedUser = array_values(array_filter($notifications, static fn (array $row): bool => $row['event_name'] === 'payment_submitted' && $row['recipient_type'] === 'user'));
    $submittedEmails = array_values(array_filter($emails, static fn (array $row): bool => $row['event_name'] === 'payment_submitted'));
    test_assert(count($submittedAdmin) === $activeAdminCount, 'Payment submitted should create one in-app notification per admin.');
    test_assert(count($submittedUser) === 1, 'Payment submitted should create one in-app notification for the user.');
    test_assert(count($submittedEmails) === ($activeAdminCount + 1), 'Payment submitted should queue one email per admin plus one for the user.');

    $duplicate = $service->emit('payment_submitted', 'payment_submitted:999:' . $payload['submitted_at'], $payload);
    test_assert($duplicate === null, 'Duplicate payment submitted event should be ignored by idempotency.');

    $service->emit('payment_approved', 'payment_approved:999:' . $payload['submitted_at'], array_merge($payload, [
        'status' => SubscriptionPaymentIntent::STATUS_APPROVED,
        'reviewed_at' => date('Y-m-d H:i:s'),
    ]));
    $service->processOutbox();
    $approvedCounts = $db->query("
        SELECT recipient_type, COUNT(*) AS aggregate
        FROM notifications
        WHERE event_name = 'payment_approved'
        GROUP BY recipient_type
    ")->fetchAll();
    $approvedEmailCount = (int) ($db->query("SELECT COUNT(*) AS aggregate FROM email_outbox WHERE event_name = 'payment_approved'")->fetch()['aggregate'] ?? 0);
    test_assert(count($approvedCounts) === 1 && (string) $approvedCounts[0]['recipient_type'] === 'user' && (int) $approvedCounts[0]['aggregate'] === 1, 'Payment approved must create only one in-app notification for the user.');
    test_assert($approvedEmailCount === 1, 'Payment approved must queue exactly one email for the user.');

    $service->emit('payment_rejected', 'payment_rejected:999:' . $payload['submitted_at'], array_merge($payload, [
        'status' => SubscriptionPaymentIntent::STATUS_REJECTED,
        'reviewed_at' => date('Y-m-d H:i:s'),
        'rejection_reason' => 'Amount is not visible.',
    ]));
    $service->processOutbox();
    $rejectedCounts = $db->query("
        SELECT recipient_type, COUNT(*) AS aggregate
        FROM notifications
        WHERE event_name = 'payment_rejected'
        GROUP BY recipient_type
    ")->fetchAll();
    $rejectedEmailCount = (int) ($db->query("SELECT COUNT(*) AS aggregate FROM email_outbox WHERE event_name = 'payment_rejected'")->fetch()['aggregate'] ?? 0);
    test_assert(count($rejectedCounts) === 1 && (string) $rejectedCounts[0]['recipient_type'] === 'user' && (int) $rejectedCounts[0]['aggregate'] === 1, 'Payment rejected must create only one in-app notification for the user.');
    test_assert($rejectedEmailCount === 1, 'Payment rejected must queue exactly one email for the user.');

    $adminPayerId = test_create_user($db, 'admin', 'payer_admin_' . bin2hex(random_bytes(3)) . '@example.com', 'Admin Payer');
    $service->emit('payment_approved', 'payment_approved:admin-payer:' . $payload['submitted_at'], array_merge($payload, [
        'user_id' => $adminPayerId,
        'user_name' => 'Admin Payer',
        'status' => SubscriptionPaymentIntent::STATUS_APPROVED,
        'reviewed_at' => date('Y-m-d H:i:s'),
    ]));
    $service->processOutbox();
    $adminPayerApproved = $db->query("
        SELECT recipient_type, COUNT(*) AS aggregate
        FROM notifications
        WHERE event_name = 'payment_approved'
          AND recipient_id = {$adminPayerId}
        GROUP BY recipient_type
    ")->fetchAll();
    test_assert(count($adminPayerApproved) === 1 && (string) $adminPayerApproved[0]['recipient_type'] === 'user' && (int) $adminPayerApproved[0]['aggregate'] === 1, 'Admin-owned payments must still be treated as user-facing in-app notifications.');
    $activeAdminCountAfterAdminPayer = (int) $db->query("SELECT COUNT(*) AS aggregate FROM users WHERE role = 'admin' AND is_active = 1")->fetch()['aggregate'];

    $intentId = $paymentIntentModel->createDraft([
        'reference' => 'notify-intent-' . bin2hex(random_bytes(4)),
        'plan_id' => $planId,
        'user_id' => $userId,
        'amount_expected_rwf' => 1500,
        'momo_number_displayed' => '+250700000000',
        'intended_activation_at' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'deadline_at' => date('Y-m-d H:i:s', strtotime('+20 hours')),
    ]);
    $paymentIntentModel->transitionSubmission($intentId, date('Y-m-d H:i:s'), false);

    $emittedCount = $maintenance->emitExpiringSoonPaymentNotifications();
    test_assert($emittedCount === 1, 'Exactly one payment expiring soon event should be emitted for a qualifying payment.');
    $maintenance->processNotificationOutbox();
    $emittedAgain = $maintenance->emitExpiringSoonPaymentNotifications();
    test_assert($emittedAgain === 0, 'Payment expiring soon should emit only once for the same payment and deadline.');

    $expiringCounts = $db->query("
        SELECT recipient_type, COUNT(*) AS aggregate
        FROM notifications
        WHERE event_name = 'payment_expiring_soon'
        GROUP BY recipient_type
    ")->fetchAll();
    $expiringByRecipientType = [];
    foreach ($expiringCounts as $row) {
        $expiringByRecipientType[(string) $row['recipient_type']] = (int) $row['aggregate'];
    }
    $expiringEmailCount = (int) ($db->query("SELECT COUNT(*) AS aggregate FROM email_outbox WHERE event_name = 'payment_expiring_soon'")->fetch()['aggregate'] ?? 0);
    test_assert(($expiringByRecipientType['admin'] ?? 0) === $activeAdminCountAfterAdminPayer, 'Payment expiring soon should create one in-app notification per admin.');
    test_assert(($expiringByRecipientType['user'] ?? 0) === 1, 'Payment expiring soon should create one in-app notification for the user.');
    test_assert($expiringEmailCount === ($activeAdminCountAfterAdminPayer + 1), 'Payment expiring soon should queue one email per admin and one for the user.');

    echo "PASS payment notification flows\n";
} catch (Throwable $exception) {
    echo 'FAIL ' . $exception->getMessage() . PHP_EOL;
    $db->rollBack();
    exit(1);
}

$db->rollBack();
exit(0);
