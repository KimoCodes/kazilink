<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/lib/Helpers.php';
require_once BASE_PATH . '/app/lib/Session.php';
require_once BASE_PATH . '/app/lib/Csrf.php';
require_once BASE_PATH . '/app/lib/Validator.php';
require_once BASE_PATH . '/app/lib/View.php';
require_once BASE_PATH . '/app/lib/Auth.php';
require_once BASE_PATH . '/app/lib/LeadCapture.php';
require_once BASE_PATH . '/app/lib/StripeCheckout.php';
require_once BASE_PATH . '/app/lib/StripeWebhook.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/models/Category.php';
require_once BASE_PATH . '/app/models/Task.php';
require_once BASE_PATH . '/app/models/Bid.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/Profile.php';
require_once BASE_PATH . '/app/models/Message.php';
require_once BASE_PATH . '/app/models/Review.php';
require_once BASE_PATH . '/app/models/AdminAudit.php';
require_once BASE_PATH . '/app/models/Payment.php';
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
require_once BASE_PATH . '/app/controllers/PaymentController.php';

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
    'payments/checkout' => ['controller' => PaymentController::class, 'action' => 'checkout', 'methods' => ['POST']],
    'payments/booking-checkout' => ['controller' => PaymentController::class, 'action' => 'checkoutBooking', 'methods' => ['POST']],
    'payments/success' => ['controller' => PaymentController::class, 'action' => 'success', 'methods' => ['GET']],
    'payments/cancel' => ['controller' => PaymentController::class, 'action' => 'cancel', 'methods' => ['GET']],
    'payments/webhook' => ['controller' => PaymentController::class, 'action' => 'webhook', 'methods' => ['POST']],
    'auth/login' => ['controller' => AuthController::class, 'action' => 'login', 'methods' => ['GET', 'POST']],
    'auth/register' => ['controller' => AuthController::class, 'action' => 'register', 'methods' => ['GET', 'POST']],
    'auth/logout' => ['controller' => AuthController::class, 'action' => 'logout', 'methods' => ['POST']],
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
    'admin/payments' => ['controller' => AdminController::class, 'action' => 'payments', 'methods' => ['GET']],
    'admin/toggle-user' => ['controller' => AdminController::class, 'action' => 'toggleUser', 'methods' => ['POST']],
    'admin/toggle-task' => ['controller' => AdminController::class, 'action' => 'toggleTask', 'methods' => ['POST']],
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
