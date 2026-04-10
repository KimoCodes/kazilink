<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/models/EmailRecipient.php';
require_once BASE_PATH . '/app/models/EmailDeliveryAttempt.php';
require_once BASE_PATH . '/app/models/EmailOutbox.php';
require_once BASE_PATH . '/app/lib/EmailTemplateCatalog.php';
require_once BASE_PATH . '/services/EmailService.php';
require_once BASE_PATH . '/app/lib/EmailDeliveryService.php';
require_once BASE_PATH . '/app/lib/Helpers.php';
require_once BASE_PATH . '/app/lib/LeadCapture.php';

echo "Triggering newsletter alert directly from LeadCapture engine...\n";

$payload = [
    'email' => 'new.subscriber@example.com',
    'audience_label' => 'Newsletter Audience',
    'source_route' => 'test_subscriber.php'
];

$success = LeadCapture::deliverNewsletterNotification($payload);

if ($success) {
    echo "Newsletter alert successfully processed and dispatched via LeadCapture!\n";
} else {
    echo "Failed to dispatch newsletter alert.\n";
}
