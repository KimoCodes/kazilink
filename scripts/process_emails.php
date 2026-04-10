<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "This script must be run from the command line.\n";
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/models/EmailRecipient.php';
require_once BASE_PATH . '/app/models/EmailDeliveryAttempt.php';
require_once BASE_PATH . '/app/models/EmailOutbox.php';
require_once BASE_PATH . '/app/lib/EmailTemplateCatalog.php';
require_once BASE_PATH . '/services/EmailService.php';
require_once BASE_PATH . '/app/lib/EmailDeliveryService.php';

echo "Starting email processing queue...\n";

$service = new EmailDeliveryService();
$count = $service->processDue(50); // Process batch of 50

echo "Processed {$count} emails.\n";
