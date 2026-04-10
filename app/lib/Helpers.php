<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config;

    if ($config === null) {
        $config = require BASE_PATH . '/app/config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function email_config(?string $key = null, mixed $default = null): mixed
{
    static $config;

    if ($config === null) {
        $config = require BASE_PATH . '/config/email_config.php';
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_base_path(): string
{
    static $basePath;

    if ($basePath !== null) {
        return $basePath;
    }

    $configuredUrl = trim((string) app_config('url', ''));
    $configuredPath = $configuredUrl !== '' ? (string) parse_url($configuredUrl, PHP_URL_PATH) : '';
    $configuredPath = trim($configuredPath, '/');

    if ($configuredPath !== '') {
        $basePath = '/' . $configuredPath;

        return $basePath;
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $scriptDir = rtrim(dirname($scriptName), '/');

    if ($scriptDir === '/.' || $scriptDir === '.') {
        $scriptDir = '';
    }

    if ($scriptDir !== '' && str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -7);
    }

    $basePath = $scriptDir === '' || $scriptDir === '/' ? '' : $scriptDir;

    return $basePath;
}

function current_route(): string
{
    return trim((string) ($_GET['route'] ?? 'home/index'), '/');
}

function route_is(array|string $routes): bool
{
    $routes = is_array($routes) ? $routes : [$routes];
    $currentRoute = current_route();

    foreach ($routes as $route) {
        $route = trim((string) $route);

        if ($route === '') {
            continue;
        }

        if (str_ends_with($route, '*')) {
            $prefix = rtrim(substr($route, 0, -1), '/');

            if ($prefix !== '' && str_starts_with($currentRoute, $prefix)) {
                return true;
            }

            continue;
        }

        if ($currentRoute === trim($route, '/')) {
            return true;
        }
    }

    return false;
}

function url_for(string $route = '', array $params = []): string
{
    $query = array_filter(array_merge(['route' => $route], $params), static fn ($value) => $value !== null && $value !== '');
    $basePath = app_base_path();
    $path = $basePath === '' ? '/' : $basePath . '/';

    return $path . ($query !== [] ? '?' . http_build_query($query) : '');
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url_for($route, $params));
    exit;
}

function absolute_url(string $route = '', array $params = []): string
{
    $relativeUrl = url_for($route, $params);
    $configuredUrl = trim((string) app_config('url', ''));

    if ($configuredUrl !== '') {
        return rtrim($configuredUrl, '/') . $relativeUrl;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1');

    return $scheme . '://' . $host . $relativeUrl;
}

function asset_url(string $path): string
{
    $basePath = app_base_path();

    return ($basePath === '' ? '' : $basePath) . '/assets/' . ltrim($path, '/');
}

function public_url(string $path): string
{
    $basePath = app_base_path();

    return ($basePath === '' ? '' : $basePath) . '/' . ltrim($path, '/');
}

function public_url_candidates(string $path): array
{
    $normalizedPath = ltrim(trim($path), '/');

    if ($normalizedPath === '') {
        return [];
    }

    $normalizedPath = preg_replace('#^public/#', '', $normalizedPath) ?? $normalizedPath;
    $basePath = app_base_path();
    $prefix = $basePath === '' ? '' : $basePath;

    $candidates = [
        $prefix . '/' . $normalizedPath,
    ];

    if (!str_starts_with($normalizedPath, 'public/')) {
        $candidates[] = $prefix . '/public/' . $normalizedPath;
    }

    return array_values(array_unique($candidates));
}

function request_headers_normalized(): array
{
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            return array_change_key_case($headers, CASE_LOWER);
        }
    }

    $headers = [];

    foreach ($_SERVER as $key => $value) {
        if (!str_starts_with($key, 'HTTP_')) {
            continue;
        }

        $normalized = strtolower(str_replace('_', '-', substr($key, 5)));
        $headers[$normalized] = (string) $value;
    }

    return $headers;
}

function momo_callback_signature(string $reference): string
{
    return hash_hmac('sha256', $reference, (string) app_config('momo.callback_secret', ''));
}

function pagination_params(int $page, int $perPage): array
{
    $page = max(1, $page);
    $perPage = max(1, $perPage);

    return [
        'page' => $page,
        'per_page' => $perPage,
        'limit' => $perPage,
        'offset' => ($page - 1) * $perPage,
    ];
}

function pagination_meta(int $page, int $perPage, int $total): array
{
    $page = max(1, $page);
    $perPage = max(1, $perPage);
    $total = max(0, $total);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'has_previous' => $page > 1,
        'has_next' => $page < $totalPages,
        'previous_page' => $page > 1 ? $page - 1 : 1,
        'next_page' => $page < $totalPages ? $page + 1 : $totalPages,
    ];
}

function storage_path(string $path = ''): string
{
    $base = BASE_PATH . '/storage';

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function old(string $key, string $default = ''): string
{
    return e(Session::getOldInput($key, $default));
}

function normalize_offline_terms_text(?string $text): string
{
    $value = trim((string) $text);

    if ($value === '') {
        return $value;
    }

    $platformName = trim((string) app_config('name', 'the platform'));
    $needle = ' does not process, collect, store, or guarantee';

    if ($platformName !== '' && !str_contains($value, $platformName) && str_contains($value, $needle)) {
        $value = str_replace($needle, ' ' . $platformName . $needle, $value);
    }

    return preg_replace('/\s{2,}/', ' ', $value) ?? $value;
}

function old_value(string $key, mixed $default = ''): string
{
    return (string) Session::getOldInput($key, $default);
}

function field_error(array $fieldErrors, string $key): ?string
{
    $messages = $fieldErrors[$key] ?? null;

    if (!is_array($messages) || $messages === []) {
        return null;
    }

    return (string) $messages[0];
}

function isPostRequest(): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
}

function ensurePostRequest(string $route, array $params = []): void
{
    if (!isPostRequest()) {
        redirect($route, $params);
    }
}

function verifyPostRequest(string $route, array $params = []): void
{
    ensurePostRequest($route, $params);
    Csrf::verifyRequest();
}

function normalize_whitespace(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    return preg_replace('/\s+/u', ' ', $value) ?? $value;
}

function moneyRwf(float|int|string|null $amount): string
{
    return 'RWF ' . number_format((float) $amount, 0, '.', ',');
}

function format_money(float|int|string|null $amount): string
{
    return moneyRwf($amount);
}

function getUserPlan(int $userId): array
{
    return PlanFeatureAccess::getUserPlan($userId);
}

function canApplyToJob(int $userId): array
{
    return PlanFeatureAccess::canApplyToJob($userId);
}

function incrementApplicationCount(int $userId): void
{
    PlanFeatureAccess::incrementApplicationCount($userId);
}

function resetDailyLimitsIfNeeded(int $userId): array
{
    return PlanFeatureAccess::resetDailyLimitsIfNeeded($userId);
}

function getJobVisibilityTime(array $plan, ?string $jobCreatedAt = null): ?string
{
    return PlanFeatureAccess::getJobVisibilityTime($plan, $jobCreatedAt);
}

function dateFmt(?string $value, string $fallback = 'Not specified'): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return $fallback;
    }

    try {
        $timezone = new DateTimeZone((string) app_config('timezone', 'Africa/Kigali'));
        $datetime = new DateTimeImmutable($value, $timezone);

        return $datetime->setTimezone($timezone)->format('d M Y, H:i');
    } catch (Throwable) {
        return $fallback;
    }
}

function format_datetime(?string $value, string $fallback = 'Not specified'): string
{
    return dateFmt($value, $fallback);
}

function session_idle_timeout_seconds(): int
{
    return max(60, (int) app_config('session.idle_timeout_seconds', 900));
}

function session_presence_window_seconds(): int
{
    return max(60, (int) app_config('session.presence_window_seconds', 300));
}

function session_heartbeat_interval_seconds(): int
{
    return max(15, (int) app_config('session.heartbeat_interval_seconds', 60));
}

function is_recent_datetime(?string $value, int $windowSeconds): bool
{
    $value = trim((string) $value);

    if ($value === '' || $windowSeconds < 1) {
        return false;
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return false;
    }

    return $timestamp >= (time() - $windowSeconds);
}

function is_user_online(array $user): bool
{
    if ((int) ($user['is_active'] ?? 0) !== 1) {
        return false;
    }

    return is_recent_datetime((string) ($user['last_seen_at'] ?? ''), session_presence_window_seconds());
}

function request_ip(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ];

    foreach ($headers as $header) {
        $value = trim((string) ($_SERVER[$header] ?? ''));

        if ($value === '') {
            continue;
        }

        if ($header === 'HTTP_X_FORWARDED_FOR') {
            $parts = array_map('trim', explode(',', $value));
            $value = (string) ($parts[0] ?? '');
        }

        if ($value !== '') {
            return mb_substr($value, 0, 120);
        }
    }

    return 'unknown';
}

function request_user_agent(): string
{
    $userAgent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

    return $userAgent !== '' ? mb_substr($userAgent, 0, 255) : 'unknown';
}

function agreement_status_label(string $status): string
{
    return match ($status) {
        'pending_acceptance' => 'Pending acceptance',
        'accepted' => 'Accepted',
        'cancelled' => 'Cancelled',
        'disputed' => 'Disputed',
        'draft' => 'Draft',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

function dispute_type_label(string $type): string
{
    return match ($type) {
        'non_payment' => 'Non-payment',
        'client_unavailable' => 'Client unavailable',
        'tasker_no_show' => 'Tasker no-show',
        'scope_change' => 'Scope change',
        'unsafe' => 'Unsafe situation',
        default => ucfirst(str_replace('_', ' ', $type)),
    };
}
