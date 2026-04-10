<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/services/EmailService.php';
require_once BASE_PATH . '/app/lib/Helpers.php';
require_once BASE_PATH . '/app/lib/LeadCapture.php';
require_once BASE_PATH . '/app/lib/SubscriptionConfig.php';
require_once BASE_PATH . '/app/lib/SubscriptionMaintenance.php';
require_once BASE_PATH . '/app/lib/NotificationMailer.php';
require_once BASE_PATH . '/app/lib/NotificationService.php';
require_once BASE_PATH . '/app/models/AppSetting.php';
require_once BASE_PATH . '/app/models/SubscriptionNotification.php';
require_once BASE_PATH . '/app/models/Plan.php';
require_once BASE_PATH . '/app/models/Subscription.php';
require_once BASE_PATH . '/app/models/SubscriptionPaymentIntent.php';
require_once BASE_PATH . '/app/models/SubscriptionPaymentIntentAudit.php';
require_once BASE_PATH . '/app/models/Notification.php';
require_once BASE_PATH . '/app/models/NotificationPreference.php';
require_once BASE_PATH . '/app/models/NotificationEventOutbox.php';
require_once BASE_PATH . '/app/models/EmailRecipient.php';
require_once BASE_PATH . '/app/models/EmailDeliveryAttempt.php';
require_once BASE_PATH . '/app/models/EmailOutbox.php';
require_once BASE_PATH . '/app/lib/EmailTemplateCatalog.php';
require_once BASE_PATH . '/app/lib/EmailDeliveryService.php';

$maintenance = new SubscriptionMaintenance();
$result = $maintenance->markExpiredActiveAsPastDueAndQueueReminders();
$delivered = $maintenance->deliverQueuedReminderStubs();
$manualResult = $maintenance->activateApprovedManualPayments();
$expiringSoonEmitted = $maintenance->emitExpiringSoonPaymentNotifications();
$notificationEventsProcessed = $maintenance->processNotificationOutbox();
$emailOutboxProcessed = $maintenance->deliverQueuedNotificationEmails();

echo json_encode([
    'past_due_marked' => $result['past_due_marked'],
    'reminders_queued' => $result['reminders_queued'],
    'reminders_delivered' => $delivered,
    'manual_payments_activated' => $manualResult['manual_payments_activated'],
    'manual_payments_expired' => $manualResult['manual_payments_expired'],
    'payment_expiring_soon_emitted' => $expiringSoonEmitted,
    'notification_events_processed' => $notificationEventsProcessed,
    'email_outbox_processed' => $emailOutboxProcessed,
    'ran_at' => date(DATE_ATOM),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
