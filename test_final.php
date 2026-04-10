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

echo "Manual Config Check:\n";
$conf = require BASE_PATH . '/app/config/app.php';
echo "Contact Email in app.php: " . ($conf['contact']['email'] ?? 'NOT FOUND') . "\n";

echo "App Config Helper Check:\n";
$helperConf = app_config('contact.email');
echo "Contact Email via helper: " . ($helperConf ?? 'NOT FOUND') . "\n";

if ($helperConf) {
    echo "Sending test notification...\n";
    $payload = [
        'email' => 'test@verify.com',
        'audience_label' => 'Verification Audience',
        'source_route' => 'test_final.php'
    ];
    $res = LeadCapture::deliverNewsletterNotification($payload);
    var_dump($res);
} else {
    echo "Aborting test due to missing config.\n";
}
