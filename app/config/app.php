<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

static $config;

if ($config !== null) {
    return $config;
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

$config = [
    'name' => $env['APP_NAME'] ?? 'Informal Marketplace',
    'env' => $env['APP_ENV'] ?? 'production',
    'debug' => filter_var($env['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'url' => rtrim($env['APP_URL'] ?? '', '/'),
    'timezone' => $env['APP_TIMEZONE'] ?? 'Africa/Kigali',
    'session_name' => $env['SESSION_NAME'] ?? 'informal_session',
    'csrf_token_name' => $env['CSRF_TOKEN_NAME'] ?? '_token',
    'contact' => [
        'email' => $env['BUSINESS_EMAIL'] ?? 'hello@yourdomain.com',
        'phone' => $env['BUSINESS_PHONE'] ?? '+250 000 000 000',
        'location' => $env['BUSINESS_LOCATION'] ?? 'Kigali, Rwanda',
        'hours' => $env['BUSINESS_HOURS'] ?? 'Mon-Fri, 08:00-18:00 CAT',
        'instagram' => $env['BUSINESS_INSTAGRAM'] ?? 'https://instagram.com/yourbrand',
        'linkedin' => $env['BUSINESS_LINKEDIN'] ?? 'https://linkedin.com/company/yourbrand',
    ],
    'stripe' => [
        'publishable_key' => $env['STRIPE_PUBLISHABLE_KEY'] ?? '',
        'secret_key' => $env['STRIPE_SECRET_KEY'] ?? '',
        'webhook_secret' => $env['STRIPE_WEBHOOK_SECRET'] ?? '',
        'currency' => $env['STRIPE_CURRENCY'] ?? 'rwf',
        'api_version' => $env['STRIPE_API_VERSION'] ?? '2026-02-25.clover',
    ],
    'db' => [
        'host' => $env['DB_HOST'] ?? '127.0.0.1',
        'port' => $env['DB_PORT'] ?? '3306',
        'name' => $env['DB_NAME'] ?? 'informal_marketplace',
        'user' => $env['DB_USER'] ?? 'root',
        'pass' => $env['DB_PASS'] ?? '',
    ],
];

return $config;
