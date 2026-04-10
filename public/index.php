<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/lib/Helpers.php';
require_once BASE_PATH . '/app/lib/Logger.php';
require_once BASE_PATH . '/app/lib/Session.php';
require_once BASE_PATH . '/app/lib/Csrf.php';
require_once BASE_PATH . '/app/lib/Validator.php';
require_once BASE_PATH . '/app/lib/View.php';
require_once BASE_PATH . '/app/lib/Auth.php';
require_once BASE_PATH . '/app/lib/LeadCapture.php';
require_once BASE_PATH . '/app/lib/SubscriptionAccess.php';
require_once BASE_PATH . '/app/lib/PlanFeatureAccess.php';
require_once BASE_PATH . '/app/lib/SubscriptionConfig.php';
require_once BASE_PATH . '/app/lib/MomoApi.php';
require_once BASE_PATH . '/app/lib/SubscriptionPaymentProcessor.php';
require_once BASE_PATH . '/app/lib/SubscriptionMaintenance.php';
require_once BASE_PATH . '/services/EmailService.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/models/Category.php';
require_once BASE_PATH . '/app/models/Task.php';
require_once BASE_PATH . '/app/models/Bid.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/Profile.php';
require_once BASE_PATH . '/app/models/Message.php';
require_once BASE_PATH . '/app/models/Review.php';
require_once BASE_PATH . '/app/models/AdminAudit.php';
require_once BASE_PATH . '/app/models/HiringAgreement.php';
require_once BASE_PATH . '/app/models/Dispute.php';
require_once BASE_PATH . '/app/models/ProductListing.php';
require_once BASE_PATH . '/app/models/ProductBid.php';
require_once BASE_PATH . '/app/models/Ad.php';
require_once BASE_PATH . '/app/models/Plan.php';
require_once BASE_PATH . '/app/models/Subscription.php';
require_once BASE_PATH . '/app/models/PromoCode.php';
require_once BASE_PATH . '/app/models/MomoTransaction.php';
require_once BASE_PATH . '/app/models/AppSetting.php';
require_once BASE_PATH . '/app/models/SiteSetting.php';
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
require_once BASE_PATH . '/app/models/NewsletterCampaign.php';
require_once BASE_PATH . '/app/lib/NotificationMailer.php';
require_once BASE_PATH . '/app/lib/NotificationService.php';
require_once BASE_PATH . '/app/lib/EmailTemplateCatalog.php';
require_once BASE_PATH . '/app/lib/EmailDeliveryService.php';
require_once BASE_PATH . '/app/lib/LeadCapture.php';
require_once BASE_PATH . '/services/EmailService.php';
require_once BASE_PATH . '/app/controllers/HomeController.php';
require_once BASE_PATH . '/app/controllers/AuthController.php';
require_once BASE_PATH . '/app/controllers/TaskController.php';
require_once BASE_PATH . '/app/controllers/BidController.php';
require_once BASE_PATH . '/app/controllers/BookingController.php';
require_once BASE_PATH . '/app/controllers/MessageController.php';
require_once BASE_PATH . '/app/controllers/ReviewController.php';
require_once BASE_PATH . '/app/controllers/ProfileController.php';
require_once BASE_PATH . '/app/controllers/AdminController.php';
require_once BASE_PATH . '/app/controllers/MarketingController.php';
require_once BASE_PATH . '/app/controllers/AgreementController.php';
require_once BASE_PATH . '/app/controllers/MarketplaceController.php';
require_once BASE_PATH . '/app/controllers/MarketplaceBidController.php';
require_once BASE_PATH . '/app/controllers/SubscriptionController.php';
require_once BASE_PATH . '/app/controllers/NotificationController.php';
require_once BASE_PATH . '/app/controllers/NewsletterCampaignController.php';

Session::start();

$appConfig = require BASE_PATH . '/app/config/app.php';

date_default_timezone_set((string) ($appConfig['timezone'] ?? 'Africa/Kigali'));

ini_set('display_errors', $appConfig['debug'] ? '1' : '0');
error_reporting(E_ALL);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$renderErrorPage = static function (int $statusCode, string $pageTitle, string $message): void {
    http_response_code($statusCode);
    echo View::render('home/index', [
        'pageTitle' => $pageTitle,
        'statusCode' => $statusCode,
        'message' => $message,
    ]);
    exit;
};

set_exception_handler(static function (Throwable $exception): void {
    Logger::logException($exception, [
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'user_agent' => request_user_agent(),
    ]);
    
    error_log($exception->getMessage());
    http_response_code(500);
    echo View::render('home/index', [
        'pageTitle' => 'Error',
        'statusCode' => 500,
        'message' => 'Please try again later.',
    ]);
});

$allowedRoutes = [
    'home/index' => ['controller' => HomeController::class, 'action' => 'index', 'methods' => ['GET']],
    'marketing/about' => ['controller' => MarketingController::class, 'action' => 'about', 'methods' => ['GET']],
    'marketing/pricing' => ['controller' => MarketingController::class, 'action' => 'pricing', 'methods' => ['GET']],
    'marketing/contact' => ['controller' => MarketingController::class, 'action' => 'contact', 'methods' => ['GET']],
    'marketing/newsletter' => ['controller' => MarketingController::class, 'action' => 'newsletter', 'methods' => ['POST']],
    'marketing/contact-submit' => ['controller' => MarketingController::class, 'action' => 'submitContact', 'methods' => ['POST']],
    'marketplace/index' => ['controller' => MarketplaceController::class, 'action' => 'index', 'methods' => ['GET']],
    'marketplace/create' => ['controller' => MarketplaceController::class, 'action' => 'create', 'methods' => ['GET', 'POST']],
    'marketplace/my-listings' => ['controller' => MarketplaceController::class, 'action' => 'myListings', 'methods' => ['GET']],
    'marketplace/offers' => ['controller' => MarketplaceController::class, 'action' => 'offers', 'methods' => ['GET']],
    'marketplace/show' => ['controller' => MarketplaceController::class, 'action' => 'show', 'methods' => ['GET']],
    'marketplace/view' => ['controller' => MarketplaceController::class, 'action' => 'view', 'methods' => ['GET']],
    'marketplace/place-bid' => ['controller' => MarketplaceBidController::class, 'action' => 'create', 'methods' => ['POST']],
    'marketplace/select-bid' => ['controller' => MarketplaceBidController::class, 'action' => 'select', 'methods' => ['POST']],
    'auth/login' => ['controller' => AuthController::class, 'action' => 'login', 'methods' => ['GET', 'POST']],
    'auth/register' => ['controller' => AuthController::class, 'action' => 'register', 'methods' => ['GET', 'POST']],
    'auth/logout' => ['controller' => AuthController::class, 'action' => 'logout', 'methods' => ['POST']],
    'auth/ping' => ['controller' => AuthController::class, 'action' => 'ping', 'methods' => ['GET']],
    'subscriptions/index' => ['controller' => SubscriptionController::class, 'action' => 'index', 'methods' => ['GET']],
    'subscriptions/subscribe' => ['controller' => SubscriptionController::class, 'action' => 'subscribe', 'methods' => ['POST']],
    'subscriptions/submit-manual-payment' => ['controller' => SubscriptionController::class, 'action' => 'submitManualPayment', 'methods' => ['POST']],
    'subscriptions/poll' => ['controller' => SubscriptionController::class, 'action' => 'poll', 'methods' => ['GET']],
    'subscriptions/callback' => ['controller' => SubscriptionController::class, 'action' => 'callback', 'methods' => ['POST']],
    'notifications/index' => ['controller' => NotificationController::class, 'action' => 'index', 'methods' => ['GET']],
    'notifications/mark-read' => ['controller' => NotificationController::class, 'action' => 'markRead', 'methods' => ['POST']],
    'tasks/index' => ['controller' => TaskController::class, 'action' => 'index', 'methods' => ['GET']],
    'tasks/browse' => ['controller' => TaskController::class, 'action' => 'browse', 'methods' => ['GET']],
    'tasks/create' => ['controller' => TaskController::class, 'action' => 'create', 'methods' => ['GET', 'POST']],
    'tasks/show' => ['controller' => TaskController::class, 'action' => 'show', 'methods' => ['GET']],
    'tasks/view' => ['controller' => TaskController::class, 'action' => 'view', 'methods' => ['GET']],
    'tasks/edit' => ['controller' => TaskController::class, 'action' => 'edit', 'methods' => ['GET', 'POST']],
    'tasks/cancel' => ['controller' => TaskController::class, 'action' => 'cancel', 'methods' => ['POST']],
    'bids/create' => ['controller' => BidController::class, 'action' => 'create', 'methods' => ['POST']],
    'bids/accept' => ['controller' => BidController::class, 'action' => 'accept', 'methods' => ['POST']],
    'bookings/index' => ['controller' => BookingController::class, 'action' => 'index', 'methods' => ['GET']],
    'bookings/show' => ['controller' => BookingController::class, 'action' => 'show', 'methods' => ['GET']],
    'bookings/complete' => ['controller' => BookingController::class, 'action' => 'complete', 'methods' => ['POST']],
    'agreements/review' => ['controller' => AgreementController::class, 'action' => 'review', 'methods' => ['GET']],
    'agreements/accept' => ['controller' => AgreementController::class, 'action' => 'accept', 'methods' => ['POST']],
    'agreements/download' => ['controller' => AgreementController::class, 'action' => 'download', 'methods' => ['GET']],
    'agreements/verify' => ['controller' => AgreementController::class, 'action' => 'verify', 'methods' => ['GET']],
    'disputes/create' => ['controller' => AgreementController::class, 'action' => 'createDispute', 'methods' => ['POST']],
    'disputes/show' => ['controller' => AgreementController::class, 'action' => 'showDispute', 'methods' => ['GET']],
    'disputes/download' => ['controller' => AgreementController::class, 'action' => 'downloadDispute', 'methods' => ['GET']],
    'messages/index' => ['controller' => MessageController::class, 'action' => 'index', 'methods' => ['GET']],
    'messages/thread' => ['controller' => MessageController::class, 'action' => 'thread', 'methods' => ['GET', 'POST']],
    'messages/poll' => ['controller' => MessageController::class, 'action' => 'poll', 'methods' => ['GET']],
    'reviews/create' => ['controller' => ReviewController::class, 'action' => 'create', 'methods' => ['GET', 'POST']],
    'profile/show' => ['controller' => ProfileController::class, 'action' => 'show', 'methods' => ['GET']],
    'profile/edit' => ['controller' => ProfileController::class, 'action' => 'edit', 'methods' => ['GET', 'POST']],
    'profile/view' => ['controller' => ProfileController::class, 'action' => 'view', 'methods' => ['GET']],
    'tasker/dashboard' => ['controller' => ProfileController::class, 'action' => 'taskerDashboard', 'methods' => ['GET']],
    'admin/dashboard' => ['controller' => AdminController::class, 'action' => 'dashboard', 'methods' => ['GET']],
    'admin/users' => ['controller' => AdminController::class, 'action' => 'users', 'methods' => ['GET']],
    'admin/tasks' => ['controller' => AdminController::class, 'action' => 'tasks', 'methods' => ['GET']],
    'admin/plans' => ['controller' => AdminController::class, 'action' => 'plans', 'methods' => ['GET']],
    'admin/save-plan' => ['controller' => AdminController::class, 'action' => 'savePlan', 'methods' => ['POST']],
    'admin/settings' => ['controller' => AdminController::class, 'action' => 'settings', 'methods' => ['GET']],
    'admin/save-settings' => ['controller' => AdminController::class, 'action' => 'saveSettings', 'methods' => ['POST']],
    'admin/theme' => ['controller' => AdminController::class, 'action' => 'theme', 'methods' => ['GET']],
    'admin/save-theme' => ['controller' => AdminController::class, 'action' => 'saveTheme', 'methods' => ['POST']],
    'admin/upload-theme-background' => ['controller' => AdminController::class, 'action' => 'uploadThemeBackground', 'methods' => ['POST']],
    'admin/promos' => ['controller' => AdminController::class, 'action' => 'promos', 'methods' => ['GET']],
    'admin/save-promo' => ['controller' => AdminController::class, 'action' => 'savePromo', 'methods' => ['POST']],
    'admin/subscriptions' => ['controller' => AdminController::class, 'action' => 'subscriptions', 'methods' => ['GET']],
    'admin/email-delivery' => ['controller' => AdminController::class, 'action' => 'emailDelivery', 'methods' => ['GET']],
    'admin/resolve-momo-transaction' => ['controller' => AdminController::class, 'action' => 'resolveMomoTransaction', 'methods' => ['POST']],
    'admin/approve-subscription-payment-intent' => ['controller' => AdminController::class, 'action' => 'approveSubscriptionPaymentIntent', 'methods' => ['POST']],
    'admin/reject-subscription-payment-intent' => ['controller' => AdminController::class, 'action' => 'rejectSubscriptionPaymentIntent', 'methods' => ['POST']],
    'admin/resend-email-delivery' => ['controller' => AdminController::class, 'action' => 'resendEmailDelivery', 'methods' => ['POST']],
    'admin/messages' => ['controller' => AdminController::class, 'action' => 'messages', 'methods' => ['GET']],
    'admin/newsletter' => ['controller' => AdminController::class, 'action' => 'newsletter', 'methods' => ['GET']],
    'admin/reply-message' => ['controller' => AdminController::class, 'action' => 'replyMessage', 'methods' => ['POST']],
    'admin/agreements' => ['controller' => AdminController::class, 'action' => 'agreements', 'methods' => ['GET']],
    'admin/disputes' => ['controller' => AdminController::class, 'action' => 'disputes', 'methods' => ['GET']],
    'admin/update-dispute-status' => ['controller' => AdminController::class, 'action' => 'updateDisputeStatus', 'methods' => ['POST']],
    'admin/ads' => ['controller' => AdminController::class, 'action' => 'ads', 'methods' => ['GET']],
    'admin/save-ad' => ['controller' => AdminController::class, 'action' => 'saveAd', 'methods' => ['POST']],
    'admin/toggle-ad' => ['controller' => AdminController::class, 'action' => 'toggleAd', 'methods' => ['POST']],
    'admin/toggle-user' => ['controller' => AdminController::class, 'action' => 'toggleUser', 'methods' => ['POST']],
    'admin/toggle-task' => ['controller' => AdminController::class, 'action' => 'toggleTask', 'methods' => ['POST']],
    'admin/newsletter-campaigns' => ['controller' => NewsletterCampaignController::class, 'action' => 'index', 'methods' => ['GET']],
    'admin/newsletter-campaigns/create' => ['controller' => NewsletterCampaignController::class, 'action' => 'create', 'methods' => ['GET', 'POST']],
    'admin/newsletter-campaigns/edit' => ['controller' => NewsletterCampaignController::class, 'action' => 'edit', 'methods' => ['GET', 'POST']],
    'admin/newsletter-campaigns/show' => ['controller' => NewsletterCampaignController::class, 'action' => 'show', 'methods' => ['GET']],
    'admin/newsletter-campaigns/schedule' => ['controller' => NewsletterCampaignController::class, 'action' => 'schedule', 'methods' => ['POST']],
    'admin/newsletter-campaigns/send' => ['controller' => NewsletterCampaignController::class, 'action' => 'send', 'methods' => ['POST']],
    'admin/newsletter-campaigns/delete' => ['controller' => NewsletterCampaignController::class, 'action' => 'delete', 'methods' => ['POST']],
];

$route = trim((string) ($_GET['route'] ?? 'home/index'), '/');

if (!isset($allowedRoutes[$route])) {
    $renderErrorPage(404, 'Page Not Found', 'The page you requested could not be found.');
}

$target = $allowedRoutes[$route];
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (!in_array($requestMethod, $target['methods'], true)) {
    header('Allow: ' . implode(', ', $target['methods']));
    $renderErrorPage(405, 'Method Not Allowed', 'That action does not allow this request method.');
}

$controller = new $target['controller']();
$action = $target['action'];

if (!method_exists($controller, $action)) {
    $renderErrorPage(404, 'Page Not Found', 'The page you requested could not be found.');
}

echo $controller->$action();
