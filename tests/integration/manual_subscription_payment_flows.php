<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$db = Database::connection();
$requiredTables = ['plans', 'subscriptions', 'subscription_payment_intents', 'subscription_payment_intent_audit'];
$createdUserIds = [];
$createdPlanId = null;

foreach ($requiredTables as $table) {
    test_assert(Database::tableExists($table), 'Missing required table for tests: ' . $table);
}

try {
    $planModel = new Plan();
    $subscriptionModel = new Subscription();
    $paymentIntentModel = new SubscriptionPaymentIntent();
    $paymentIntentAuditModel = new SubscriptionPaymentIntentAudit();
    $maintenance = new SubscriptionMaintenance();
    $timezone = new DateTimeZone((string) app_config('timezone', 'Africa/Kigali'));

    $planId = $planModel->create([
        'slug' => 'manual-premium-' . bin2hex(random_bytes(3)),
        'name' => 'Manual Premium',
        'price_rwf' => 10000,
        'visibility_level' => 3,
        'max_applications_per_day' => 25,
        'priority_level' => 3,
        'job_alert_delay_minutes' => 0,
        'max_active_jobs' => 10,
        'commission_discount' => 10,
        'badge_name' => 'Premium',
        'active' => true,
    ]);
    $createdPlanId = $planId;

    $activationUserId = test_create_user($db, 'tasker', 'manual_activation_' . bin2hex(random_bytes(3)) . '@example.com', 'Manual Activation');
    $createdUserIds[] = $activationUserId;
    $subscriptionId = $subscriptionModel->createTrialForUser($activationUserId);
    $activationReference = 'manual-activation-' . bin2hex(random_bytes(4));
    $activationAt = (new DateTimeImmutable('now', $timezone))->modify('-1 day');
    $activationDeadline = $activationAt->modify('-48 hours');
    $activationIntentId = $paymentIntentModel->createDraft([
        'reference' => $activationReference,
        'plan_id' => $planId,
        'user_id' => $activationUserId,
        'amount_expected_rwf' => 10000,
        'momo_number_displayed' => '+250700000000',
        'intended_activation_at' => $activationAt->format('Y-m-d H:i:s'),
        'deadline_at' => $activationDeadline->format('Y-m-d H:i:s'),
    ]);
    $subscriptionModel->markPaymentPending($subscriptionId, $planId, $activationReference);
    $paymentIntentModel->updateProof($activationIntentId, [
        'amount_paid_rwf' => 10000,
        'payer_phone' => '250788111111',
        'screenshot_url' => 'uploads/subscription-payment-proofs/activation-proof.png',
        'screenshot_hash' => sha1('activation-proof'),
    ]);
    $paymentIntentAuditModel->create([
        'payment_intent_id' => $activationIntentId,
        'actor_user_id' => $activationUserId,
        'actor_type' => 'user',
        'action' => 'submitted',
        'from_status' => SubscriptionPaymentIntent::STATUS_DRAFT,
        'to_status' => SubscriptionPaymentIntent::STATUS_SUBMITTED,
        'reason' => 'Integration test submission at the exact deadline.',
    ]);
    $paymentIntentModel->transitionSubmission($activationIntentId, $activationDeadline->format('Y-m-d H:i:s'), false);
    $paymentIntentModel->approve($activationIntentId, $activationUserId, date('Y-m-d H:i:s'));
    $maintenanceResult = $maintenance->activateApprovedManualPayments();
    $activatedIntent = $paymentIntentModel->findById($activationIntentId);
    $activatedSubscription = $subscriptionModel->currentForUser($activationUserId);
    test_assert(($maintenanceResult['manual_payments_activated'] ?? 0) >= 1, 'Approved manual payments should activate when their activation time arrives.');
    test_assert($activatedIntent !== null && (string) $activatedIntent['status'] === SubscriptionPaymentIntent::STATUS_ACTIVATED, 'Approved manual payment should move to activated.');
    test_assert($activatedSubscription !== null && (string) $activatedSubscription['status'] === 'active', 'Approved manual payment should activate the subscription.');

    $reviewUserId = test_create_user($db, 'tasker', 'manual_review_' . bin2hex(random_bytes(3)) . '@example.com', 'Manual Review');
    $createdUserIds[] = $reviewUserId;
    $reviewSubscriptionId = $subscriptionModel->createTrialForUser($reviewUserId);
    $reviewReference = 'manual-review-' . bin2hex(random_bytes(4));
    $reviewActivationAt = (new DateTimeImmutable('now', $timezone))->modify('+5 days');
    $reviewDeadline = $reviewActivationAt->modify('-48 hours');
    $reviewIntentId = $paymentIntentModel->createDraft([
        'reference' => $reviewReference,
        'plan_id' => $planId,
        'user_id' => $reviewUserId,
        'amount_expected_rwf' => 10000,
        'momo_number_displayed' => '+250700000000',
        'intended_activation_at' => $reviewActivationAt->format('Y-m-d H:i:s'),
        'deadline_at' => $reviewDeadline->format('Y-m-d H:i:s'),
    ]);
    $subscriptionModel->markPaymentPending($reviewSubscriptionId, $planId, $reviewReference);
    $paymentIntentModel->updateProof($reviewIntentId, [
        'amount_paid_rwf' => 10000,
        'payer_phone' => '250788222222',
        'screenshot_url' => 'uploads/subscription-payment-proofs/review-proof-v1.png',
        'screenshot_hash' => sha1('review-proof-v1'),
    ]);
    $paymentIntentModel->transitionSubmission($reviewIntentId, (new DateTimeImmutable('now', $timezone))->format('Y-m-d H:i:s'), false);
    $paymentIntentModel->reject($reviewIntentId, $reviewUserId, date('Y-m-d H:i:s'), 'The uploaded screenshot does not clearly show the transaction amount.');
    $rejectedIntent = $paymentIntentModel->findById($reviewIntentId);
    test_assert($rejectedIntent !== null && (string) $rejectedIntent['status'] === SubscriptionPaymentIntent::STATUS_REJECTED, 'Admin rejection should move the payment proof to rejected.');
    test_assert((string) $rejectedIntent['rejection_reason'] !== '', 'Rejection should store a reason for the user.');

    $paymentIntentModel->updateProof($reviewIntentId, [
        'amount_paid_rwf' => 10000,
        'payer_phone' => '250788222222',
        'screenshot_url' => 'uploads/subscription-payment-proofs/review-proof-v2.png',
        'screenshot_hash' => sha1('review-proof-v2'),
    ]);
    $paymentIntentModel->transitionSubmission($reviewIntentId, (new DateTimeImmutable('now', $timezone))->format('Y-m-d H:i:s'), false);
    $paymentIntentModel->approve($reviewIntentId, $reviewUserId, date('Y-m-d H:i:s'));
    $resubmittedIntent = $paymentIntentModel->findById($reviewIntentId);
    test_assert($resubmittedIntent !== null && (string) $resubmittedIntent['status'] === SubscriptionPaymentIntent::STATUS_APPROVED, 'A rejected proof should be able to return to approved after a valid re-upload before the deadline.');

    $overlapUserId = test_create_user($db, 'tasker', 'manual_overlap_' . bin2hex(random_bytes(3)) . '@example.com', 'Manual Overlap');
    $createdUserIds[] = $overlapUserId;
    $overlapSubscriptionId = $subscriptionModel->createTrialForUser($overlapUserId);
    $firstOverlapReference = 'manual-overlap-a-' . bin2hex(random_bytes(4));
    $firstOverlapActivationAt = (new DateTimeImmutable('now', $timezone))->modify('-3 hours');
    $firstOverlapDeadline = $firstOverlapActivationAt->modify('-48 hours');
    $firstOverlapIntentId = $paymentIntentModel->createDraft([
        'reference' => $firstOverlapReference,
        'plan_id' => $planId,
        'user_id' => $overlapUserId,
        'amount_expected_rwf' => 10000,
        'momo_number_displayed' => '+250700000000',
        'intended_activation_at' => $firstOverlapActivationAt->format('Y-m-d H:i:s'),
        'deadline_at' => $firstOverlapDeadline->format('Y-m-d H:i:s'),
    ]);
    $subscriptionModel->markPaymentPending($overlapSubscriptionId, $planId, $firstOverlapReference);
    $paymentIntentModel->updateProof($firstOverlapIntentId, [
        'amount_paid_rwf' => 10000,
        'payer_phone' => '250788444444',
        'screenshot_url' => 'uploads/subscription-payment-proofs/overlap-proof-a.png',
        'screenshot_hash' => sha1('overlap-proof-a'),
    ]);
    $paymentIntentModel->transitionSubmission($firstOverlapIntentId, $firstOverlapDeadline->format('Y-m-d H:i:s'), false);
    $paymentIntentModel->approve($firstOverlapIntentId, $overlapUserId, date('Y-m-d H:i:s'));

    $secondOverlapReference = 'manual-overlap-b-' . bin2hex(random_bytes(4));
    $secondOverlapActivationAt = (new DateTimeImmutable('now', $timezone))->modify('+4 days');
    $secondOverlapDeadline = $secondOverlapActivationAt->modify('-48 hours');
    $secondOverlapIntentId = $paymentIntentModel->createDraft([
        'reference' => $secondOverlapReference,
        'plan_id' => $planId,
        'user_id' => $overlapUserId,
        'amount_expected_rwf' => 10000,
        'momo_number_displayed' => '+250700000000',
        'intended_activation_at' => $secondOverlapActivationAt->format('Y-m-d H:i:s'),
        'deadline_at' => $secondOverlapDeadline->format('Y-m-d H:i:s'),
    ]);
    $subscriptionModel->markPaymentPending($overlapSubscriptionId, $planId, $secondOverlapReference);

    $overlapMaintenance = $maintenance->activateApprovedManualPayments();
    $overlapActivatedIntent = $paymentIntentModel->findById($firstOverlapIntentId);
    $overlapSubscription = $subscriptionModel->currentForUser($overlapUserId);
    test_assert(($overlapMaintenance['manual_payments_activated'] ?? 0) >= 1, 'Approved intents should still activate even if a newer draft payment request exists.');
    test_assert($overlapActivatedIntent !== null && (string) $overlapActivatedIntent['status'] === SubscriptionPaymentIntent::STATUS_ACTIVATED, 'The older approved intent should activate successfully even after a newer draft overwrote the pending reference.');
    test_assert($overlapSubscription !== null && (string) $overlapSubscription['momo_reference'] === $firstOverlapReference, 'Activation should restore the matching payment reference before activating the approved intent.');
    test_assert((int) $secondOverlapIntentId > 0, 'A newer draft intent should still exist after the earlier approved intent activates.');

    $lateUserId = test_create_user($db, 'tasker', 'manual_late_' . bin2hex(random_bytes(3)) . '@example.com', 'Manual Late');
    $createdUserIds[] = $lateUserId;
    $lateSubscriptionId = $subscriptionModel->createTrialForUser($lateUserId);
    $lateReference = 'manual-late-' . bin2hex(random_bytes(4));
    $lateActivationAt = (new DateTimeImmutable('now', $timezone))->modify('-2 hours');
    $lateDeadline = $lateActivationAt->modify('-48 hours');
    $lateIntentId = $paymentIntentModel->createDraft([
        'reference' => $lateReference,
        'plan_id' => $planId,
        'user_id' => $lateUserId,
        'amount_expected_rwf' => 10000,
        'momo_number_displayed' => '+250700000000',
        'intended_activation_at' => $lateActivationAt->format('Y-m-d H:i:s'),
        'deadline_at' => $lateDeadline->format('Y-m-d H:i:s'),
    ]);
    $subscriptionModel->markPaymentPending($lateSubscriptionId, $planId, $lateReference);
    $paymentIntentModel->updateProof($lateIntentId, [
        'amount_paid_rwf' => 10000,
        'payer_phone' => '250788333333',
        'screenshot_url' => 'uploads/subscription-payment-proofs/late-proof.png',
        'screenshot_hash' => sha1('late-proof'),
    ]);
    $lateSubmittedAt = $lateDeadline->modify('+1 second');
    $paymentIntentModel->transitionSubmission($lateIntentId, $lateSubmittedAt->format('Y-m-d H:i:s'), true);
    $lateIntent = $paymentIntentModel->findById($lateIntentId);
    test_assert($lateIntent !== null && (int) $lateIntent['is_late'] === 1, 'Submitting after the 48-hour deadline should mark the payment proof as late.');
    $lateMaintenance = $maintenance->activateApprovedManualPayments();
    $expiredLateIntent = $paymentIntentModel->findById($lateIntentId);
    test_assert(($lateMaintenance['manual_payments_expired'] ?? 0) >= 1, 'Stale late proofs should expire once the activation window passes.');
    test_assert($expiredLateIntent !== null && (string) $expiredLateIntent['status'] === SubscriptionPaymentIntent::STATUS_EXPIRED, 'Late pending payment proofs should expire after the activation time passes.');

    echo "PASS manual subscription payment flows\n";
} catch (Throwable $exception) {
    echo 'FAIL ' . $exception->getMessage() . PHP_EOL;
    $exitCode = 1;
} finally {
    if ($createdUserIds !== []) {
        $placeholders = implode(',', array_fill(0, count($createdUserIds), '?'));

        $db->prepare('DELETE FROM subscription_payment_intent_audit WHERE payment_intent_id IN (SELECT id FROM subscription_payment_intents WHERE user_id IN (' . $placeholders . '))')
            ->execute($createdUserIds);
        $db->prepare('DELETE FROM subscription_payment_intents WHERE user_id IN (' . $placeholders . ')')
            ->execute($createdUserIds);
        $db->prepare('DELETE FROM subscriptions WHERE user_id IN (' . $placeholders . ')')
            ->execute($createdUserIds);
        $db->prepare('DELETE FROM profiles WHERE user_id IN (' . $placeholders . ')')
            ->execute($createdUserIds);
        $db->prepare('DELETE FROM users WHERE id IN (' . $placeholders . ')')
            ->execute($createdUserIds);
    }

    if ($createdPlanId !== null) {
        $db->prepare('DELETE FROM plans WHERE id = ?')->execute([$createdPlanId]);
    }
}

exit($exitCode ?? 0);
