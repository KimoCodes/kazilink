<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/lib/Helpers.php';
require_once BASE_PATH . '/app/lib/Session.php';
require_once BASE_PATH . '/app/lib/Auth.php';
require_once BASE_PATH . '/app/lib/View.php';
require_once BASE_PATH . '/app/lib/Validator.php';
require_once BASE_PATH . '/app/models/Task.php';
require_once BASE_PATH . '/app/models/Category.php';
require_once BASE_PATH . '/app/models/Bid.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/models/Ad.php';
require_once BASE_PATH . '/app/models/Profile.php';
require_once BASE_PATH . '/app/models/HiringAgreement.php';
require_once BASE_PATH . '/app/models/Subscription.php';
require_once BASE_PATH . '/app/models/Plan.php';
require_once BASE_PATH . '/app/lib/SubscriptionAccess.php';
require_once BASE_PATH . '/app/lib/PlanFeatureAccess.php';
require_once BASE_PATH . '/app/controllers/TaskController.php';

Session::start();

// Simulate being signed in as client@example.com (User 2)
$user = [
    'id' => 2,
    'email' => 'client@example.com',
    'role' => 'client',
    'full_name' => 'Client User'
];
Session::put('auth_user', $user);

// Mock the redirect function to see if it's called
function mock_redirect($route, $params) {
    echo "REDIRECT CALLED TO: " . $route . " with params: " . json_encode($params) . "\n";
    die("STOPPED BY REDIRECT\n");
}

// Since I can't easily mock the global redirect function if it's already defined...
// I'll just check if TaskController::edit() returns something or exits.

$controller = new TaskController();
$_GET['id'] = 2; // Task 2 belongs to User 11

echo "Attempting to edit Task 2 (owned by User 11) while logged in as User 2...\n";
try {
    $result = $controller->edit();
    echo "FAILURE: The edit page was rendered! Result content length: " . strlen($result) . "\n";
} catch (Exception $e) {
    echo "Caught exception: " . $e->getMessage() . "\n";
}
