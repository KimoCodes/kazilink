<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/models/EmailOutbox.php';
require_once BASE_PATH . '/app/lib/EmailTemplateCatalog.php';

$outbox = new EmailOutbox();
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM email_outbox ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

$catalog = new EmailTemplateCatalog();

foreach ($emails as $email) {
    echo "========================================================\n";
    echo "To: " . $email['recipient_email'] . "\n";
    echo "Subject: " . $email['subject'] . "\n";
    echo "Template: " . $email['template_name'] . "\n";
    echo "Status: " . $email['status'] . "\n";
    echo "--------------------------------------------------------\n";
    
    $payload = json_decode((string) ($email['template_data_json'] ?? '{}'), true);
    $payload['subject'] = $email['subject'];
    
    try {
        $rendered = $catalog->render($email['template_name'], $payload);
        echo "PLAIN TEXT BODY:\n";
        echo $rendered['text'] . "\n\n";
        echo "HTML BODY LENGTH: " . strlen($rendered['html']) . " characters\n";
    } catch (Exception $e) {
        echo "Error rendering: " . $e->getMessage() . "\n";
    }
    echo "========================================================\n\n";
}
