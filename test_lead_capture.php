<?php
define('BASE_PATH', __DIR__);
require 'app/lib/Helpers.php';
require 'app/lib/EmailDeliveryService.php';
require 'app/lib/LeadCapture.php';

$payload = ['email' => 'new.subscriber@example.com', 'audience_label' => 'Newsletter Audience', 'source_route' => 'test'];

$recipient = trim((string) app_config('contact.email', ''));
$email = trim((string) ($payload['email'] ?? ''));

echo "Recipient: '$recipient'\nEmail: '$email'\n";

if ($recipient === '') {
    echo "Fails: recipient is empty\n";
} elseif (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    echo "Fails: recipient invalid email\n";
} elseif ($email === '') {
    echo "Fails: email empty\n";
} else {
    echo "Variables PASSED.\n";
}
