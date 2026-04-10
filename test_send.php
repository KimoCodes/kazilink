<?php
define('BASE_PATH', __DIR__);
require_once 'app/lib/Helpers.php';
require_once 'app/lib/EmailDeliveryService.php';
require_once 'app/lib/LeadCapture.php';
require_once 'app/lib/EmailTemplateCatalog.php';
require_once 'services/EmailService.php';
require_once 'app/models/EmailOutbox.php';
require_once 'app/models/EmailRecipient.php';
require_once 'app/models/EmailDeliveryAttempt.php';
require_once 'app/config/database.php';

echo "DEBUG: Calling app_config()...\n";
$all = app_config();
echo "Config type: " . gettype($all) . "\n";
if (is_array($all)) {
    echo "Keys: " . implode(', ', array_keys($all)) . "\n";
    if (isset($all['contact'])) {
        echo "Contact type: " . gettype($all['contact']) . "\n";
        echo "Contact email: " . ($all['contact']['email'] ?? 'EMPTY') . "\n";
    } else {
        echo "Contact key missing\n";
    }
} else {
    echo "Config IS NOT AN ARRAY: ";
    var_dump($all);
}

$email = app_config('contact.email');
echo "Direct helper call for 'contact.email': " . ($email ?? 'NULL') . "\n";

$payload = [
    'email' => 'test_success@verify.com',
    'audience_label' => 'Final Test',
    'source_route' => 'test_send.php'
];

echo "Attempting to send...\n";
$res = LeadCapture::deliverNewsletterNotification($payload);

if ($res) {
    echo "SUCCESS: Email queued and sent synchronously!\n";
} else {
    echo "FAILURE: Could not send email.\n";
}
