<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/lib/Helpers.php';
require_once BASE_PATH . '/app/lib/LeadCapture.php';
require_once BASE_PATH . '/app/lib/SubscriptionConfig.php';
require_once BASE_PATH . '/app/lib/SubscriptionMaintenance.php';
require_once BASE_PATH . '/app/models/AppSetting.php';
require_once BASE_PATH . '/app/models/SubscriptionNotification.php';

$maintenance = new SubscriptionMaintenance();
$result = $maintenance->markExpiredActiveAsPastDueAndQueueReminders();
$delivered = $maintenance->deliverQueuedReminderStubs();

echo json_encode([
    'past_due_marked' => $result['past_due_marked'],
    'reminders_queued' => $result['reminders_queued'],
    'reminders_delivered' => $delivered,
    'ran_at' => date(DATE_ATOM),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
