<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$db = Database::connection();
$requiredTables = ['plans', 'subscriptions', 'promo_codes', 'promo_redemptions', 'momo_transactions', 'app_settings'];

foreach ($requiredTables as $table) {
    test_assert(Database::tableExists($table), 'Missing required table for tests: ' . $table);
}

$db->beginTransaction();

try {
    $planModel = new Plan();
    $subscriptionModel = new Subscription();
    $promoModel = new PromoCode();
    $transactionModel = new MomoTransaction();
    $processor = new SubscriptionPaymentProcessor();

    $basic = $planModel->getBasicPlan();
    test_assert($basic !== null, 'Basic plan must exist for integration tests.');

    $emailBase = 'itest_' . bin2hex(random_bytes(4));
    $userId = test_create_user($db, 'tasker', $emailBase . '@example.com', 'Integration Tester');
    $subscriptionModel->createTrialForUser($userId);
    $subscription = $subscriptionModel->currentForUser($userId);
    test_assert($subscription !== null, 'Trial subscription should be created.');

    $promoId = $promoModel->create([
        'code' => 'PROMO' . strtoupper(bin2hex(random_bytes(3))),
        'type' => 'percent',
        'amount' => 50,
        'max_redemptions' => 10,
        'expires_at' => null,
        'active' => true,
        'target_user_ids' => [$userId],
    ]);
    $promo = $promoModel->findById($promoId);
    test_assert($promo !== null, 'Promo should be created.');
    test_assert($promoModel->validationForUser((string) $promo['code'], $userId) !== null, 'Promo should validate for targeted user.');

    $db->prepare('
        UPDATE subscriptions
        SET trial_ends_at = DATE_ADD(NOW(), INTERVAL 10 DAY),
            current_period_ends_at = DATE_ADD(NOW(), INTERVAL 10 DAY),
            updated_at = NOW()
        WHERE user_id = :user_id
    ')->execute(['user_id' => $userId]);

    $transactionRef = 'itest-' . bin2hex(random_bytes(6));
    $transactionModel->create([
        'user_id' => $userId,
        'amount_rwf' => 1500,
        'external_ref' => $transactionRef,
        'status' => 'pending',
        'raw_payload_json' => json_encode([
            'plan_id' => (int) $basic['id'],
            'promo_code_id' => (int) $promoId,
            'source' => 'integration_test',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);

    $processor->processSuccessfulTransaction($transactionRef, ['status' => 'SUCCESSFUL']);
    $afterFirstSuccess = $subscriptionModel->currentForUser($userId);
    test_assert($afterFirstSuccess !== null, 'Subscription should still exist after activation.');
    test_assert((string) $afterFirstSuccess['status'] === 'active', 'Subscription should become active after successful payment.');
    test_assert(strtotime((string) $afterFirstSuccess['current_period_ends_at']) > strtotime((string) $afterFirstSuccess['trial_ends_at']), 'Trial rollover should extend after the remaining trial.');

    $periodEndAfterFirstSuccess = (string) $afterFirstSuccess['current_period_ends_at'];
    $processor->processSuccessfulTransaction($transactionRef, ['status' => 'SUCCESSFUL']);
    $afterDuplicateSuccess = $subscriptionModel->currentForUser($userId);
    test_assert((string) $afterDuplicateSuccess['current_period_ends_at'] === $periodEndAfterFirstSuccess, 'Duplicate callbacks must not extend the subscription twice.');

    $countRedemptionsStmt = $db->prepare('SELECT COUNT(*) AS aggregate FROM promo_redemptions WHERE promo_code_id = :promo_code_id AND user_id = :user_id');
    $countRedemptionsStmt->execute([
        'promo_code_id' => $promoId,
        'user_id' => $userId,
    ]);
    $redemptionCount = (int) (($countRedemptionsStmt->fetch()['aggregate'] ?? 0));
    test_assert($redemptionCount === 1, 'Promo redemption should be recorded exactly once even after duplicate callbacks.');

    echo "PASS subscription integration flows\n";
} catch (Throwable $exception) {
    echo 'FAIL ' . $exception->getMessage() . PHP_EOL;
    $db->rollBack();
    exit(1);
}

$db->rollBack();
exit(0);
