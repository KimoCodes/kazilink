<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/lib/Helpers.php';
require_once BASE_PATH . '/app/lib/LeadCapture.php';
require_once BASE_PATH . '/app/lib/SubscriptionConfig.php';
require_once BASE_PATH . '/app/lib/SubscriptionAccess.php';
require_once BASE_PATH . '/app/lib/SubscriptionPaymentProcessor.php';
require_once BASE_PATH . '/app/lib/SubscriptionMaintenance.php';
require_once BASE_PATH . '/services/EmailService.php';
require_once BASE_PATH . '/app/lib/NotificationMailer.php';
require_once BASE_PATH . '/app/lib/NotificationService.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/models/Plan.php';
require_once BASE_PATH . '/app/models/Subscription.php';
require_once BASE_PATH . '/app/models/PromoCode.php';
require_once BASE_PATH . '/app/models/MomoTransaction.php';
require_once BASE_PATH . '/app/models/AppSetting.php';
require_once BASE_PATH . '/app/models/SubscriptionNotification.php';
require_once BASE_PATH . '/app/models/MomoWebhookLog.php';
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

function test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function test_create_user(PDO $db, string $role, string $email, string $fullName): int
{
    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    $statement = $db->prepare('
        INSERT INTO users (email, password_hash, role, is_active, failed_login_attempts, last_failed_login_at, last_login_at, last_seen_at, last_logout_at, created_at, updated_at)
        VALUES (:email, :password_hash, :role, 1, 0, NULL, NULL, NULL, NULL, NOW(), NOW())
    ');
    $statement->execute([
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
    ]);

    $userId = (int) $db->lastInsertId();
    $profile = $db->prepare('
        INSERT INTO profiles (user_id, full_name, phone, city, region, country, bio, avatar_path, skills_summary, created_at, updated_at)
        VALUES (:user_id, :full_name, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NOW())
    ');
    $profile->execute([
        'user_id' => $userId,
        'full_name' => $fullName,
    ]);

    return $userId;
}
