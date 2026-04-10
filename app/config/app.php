<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

$env = [];
$envFile = BASE_PATH . '/.env';

if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");
        $env[$key] = $value;
    }
}

return [
    'name' => $env['APP_NAME'] ?? 'Informal Marketplace',
    'env' => $env['APP_ENV'] ?? 'production',
    'debug' => filter_var($env['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'url' => rtrim($env['APP_URL'] ?? '', '/'),
    'timezone' => $env['APP_TIMEZONE'] ?? 'Africa/Kigali',
    'session_name' => $env['SESSION_NAME'] ?? 'informal_session',
    'session' => [
        'idle_timeout_seconds' => max(60, (int) ($env['SESSION_IDLE_TIMEOUT_SECONDS'] ?? 900)),
        'presence_window_seconds' => max(60, (int) ($env['SESSION_PRESENCE_WINDOW_SECONDS'] ?? 300)),
        'heartbeat_interval_seconds' => max(15, (int) ($env['SESSION_HEARTBEAT_INTERVAL_SECONDS'] ?? 60)),
    ],
    'csrf_token_name' => $env['CSRF_TOKEN_NAME'] ?? '_token',
    'subscriptions' => [
        'grace_days' => (int) ($env['SUBSCRIPTION_GRACE_DAYS'] ?? 5),
    ],
    'momo' => [
        'base_url' => $env['MOMO_BASE_URL'] ?? '',
        'target_environment' => $env['MOMO_TARGET_ENVIRONMENT'] ?? 'sandbox',
        'primary_key' => $env['MOMO_PRIMARY_KEY'] ?? '',
        'api_user' => $env['MOMO_API_USER'] ?? '',
        'api_key' => $env['MOMO_API_KEY'] ?? '',
        'currency' => $env['MOMO_CURRENCY'] ?? 'RWF',
        'display_number' => $env['MOMO_DISPLAY_NUMBER'] ?? ($env['BUSINESS_PHONE'] ?? '+250 000 000 000'),
        'callback_secret' => $env['MOMO_CALLBACK_SECRET'] ?? '',
        'callback_allowlist' => $env['MOMO_CALLBACK_ALLOWLIST'] ?? '',
    ],
    'contact' => [
        'email' => $env['BUSINESS_EMAIL'] ?? 'hello@yourdomain.com',
        'phone' => $env['BUSINESS_PHONE'] ?? '+250 000 000 000',
        'location' => $env['BUSINESS_LOCATION'] ?? 'Kigali, Rwanda',
        'hours' => $env['BUSINESS_HOURS'] ?? 'Mon-Fri, 08:00-18:00 CAT',
        'instagram' => $env['BUSINESS_INSTAGRAM'] ?? 'https://instagram.com/yourbrand',
        'linkedin' => $env['BUSINESS_LINKEDIN'] ?? 'https://linkedin.com/company/yourbrand',
    ],
    'notifications' => [
        'expiring_soon_hours' => max(1, (int) ($env['NOTIFICATION_PAYMENT_EXPIRING_SOON_HOURS'] ?? 24)),
        'sendgrid_api_key' => $env['SENDGRID_API_KEY'] ?? '',
        'from_email' => $env['NOTIFICATION_FROM_EMAIL'] ?? ($env['BUSINESS_EMAIL'] ?? 'hello@yourdomain.com'),
        'from_name' => $env['NOTIFICATION_FROM_NAME'] ?? ($env['APP_NAME'] ?? 'Informal Marketplace'),
    ],
    'email' => [
        'enabled' => filter_var($env['EMAIL_ENABLED'] ?? true, FILTER_VALIDATE_BOOL),
        'sandbox_mode' => filter_var($env['EMAIL_SANDBOX_MODE'] ?? false, FILTER_VALIDATE_BOOL),
        'default_timeout_seconds' => max(5, (int) ($env['EMAIL_DEFAULT_TIMEOUT_SECONDS'] ?? 10)),
        'reply_to_email' => $env['EMAIL_REPLY_TO_ADDRESS'] ?? ($env['BUSINESS_EMAIL'] ?? 'hello@yourdomain.com'),
        'reply_to_name' => $env['EMAIL_REPLY_TO_NAME'] ?? ($env['APP_NAME'] ?? 'Informal Marketplace'),
    ],
    'db' => [
        'host' => $env['DB_HOST'] ?? '127.0.0.1',
        'port' => $env['DB_PORT'] ?? '3306',
        'name' => $env['DB_NAME'] ?? 'informal_marketplace',
        'user' => $env['DB_USER'] ?? 'root',
        'pass' => $env['DB_PASS'] ?? '',
    ],
];
