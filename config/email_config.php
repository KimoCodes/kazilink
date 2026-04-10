<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
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
        $env[trim($key)] = trim(trim($value), "\"'");
    }
}

return [
    'transport' => 'smtp',
    'enabled' => filter_var($env['EMAIL_ENABLED'] ?? true, FILTER_VALIDATE_BOOL),
    'sandbox_mode' => filter_var($env['EMAIL_SANDBOX_MODE'] ?? false, FILTER_VALIDATE_BOOL),
    'host' => $env['SMTP_HOST'] ?? 'mail.yourdomain.com',
    'port' => max(1, (int) ($env['SMTP_PORT'] ?? 587)),
    'username' => $env['SMTP_USER'] ?? 'no-reply@yourdomain.com',
    'password' => $env['SMTP_PASS'] ?? '',
    'secure' => strtolower((string) ($env['SMTP_SECURE'] ?? 'tls')),
    'timeout' => max(5, (int) ($env['SMTP_TIMEOUT_SECONDS'] ?? 20)),
    'from_email' => $env['NOTIFICATION_FROM_EMAIL'] ?? ($env['SMTP_USER'] ?? 'no-reply@yourdomain.com'),
    'from_name' => $env['NOTIFICATION_FROM_NAME'] ?? ($env['APP_NAME'] ?? 'Kazilink'),
    'reply_to_email' => $env['EMAIL_REPLY_TO_ADDRESS'] ?? ($env['BUSINESS_EMAIL'] ?? ($env['SMTP_USER'] ?? 'no-reply@yourdomain.com')),
    'reply_to_name' => $env['EMAIL_REPLY_TO_NAME'] ?? ($env['APP_NAME'] ?? 'Kazilink'),
    'support_email' => $env['SUPPORT_EMAIL'] ?? ($env['BUSINESS_EMAIL'] ?? 'support@yourdomain.com'),
    'support_phone' => $env['SUPPORT_PHONE'] ?? ($env['BUSINESS_PHONE'] ?? '+250 000 000 000'),
];
