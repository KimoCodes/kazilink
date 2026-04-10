<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/services/EmailService.php';

$emailService = new EmailService();

$to = 'b.benoit@alustudent.com';
$subject = 'Test Request from Kazilink Platform';
$htmlBody = "<h1>hey</h1>";
$plainBody = "hey";

echo "Sending email to {$to}...\n";

$result = $emailService->sendRenderedEmail(
    $to,
    $subject,
    $htmlBody,
    $plainBody
);

if ($result['ok']) {
    echo "Email sent successfully via ZubaHost!\n";
} else {
    echo "Failed to send email. Error: " . ($result['reason'] ?? 'Unknown Error') . "\n";
}
