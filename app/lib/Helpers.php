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

function pricing_plans(): array
{
    return [
        'starter' => [
            'id' => 'starter',
            'name' => 'Starter Coordination',
            'amount' => 15000,
            'billing_label' => 'One-time payment',
            'description' => 'For one straightforward request that needs fast, professional coordination.',
            'badge' => 'Best for first-time clients',
            'highlighted' => false,
            'cta' => 'Pay for Starter',
            'features' => [
                'One assisted task request',
                'Clear pricing review before matching',
                'Booking follow-up inside the platform',
            ],
        ],
        'growth' => [
            'id' => 'growth',
            'name' => 'Priority Match',
            'amount' => 35000,
            'billing_label' => 'One-time payment',
            'description' => 'For recurring households or busy teams that want quicker turnaround and tighter follow-up.',
            'badge' => 'Most practical',
            'highlighted' => true,
            'cta' => 'Pay for Priority',
            'features' => [
                'Priority review of your request',
                'Faster shortlist coordination',
                'One follow-up support window after booking',
            ],
        ],
        'scale' => [
            'id' => 'scale',
            'name' => 'Team Support',
            'amount' => 90000,
            'billing_label' => 'One-time payment',
            'description' => 'For offices, hosts, and operators managing several moving parts at once.',
            'badge' => 'For business use',
            'highlighted' => false,
            'cta' => 'Pay for Team Support',
            'features' => [
                'Multi-request coordination for one cycle',
                'Priority communication window',
                'Concise post-booking handoff summary',
            ],
        ],
    ];
}

function pricing_plan(?string $planId): ?array
{
    $planId = trim((string) $planId);
    $plans = pricing_plans();

    return $plans[$planId] ?? null;
}

function payments_enabled(): bool
{
    $secretKey = trim((string) app_config('stripe.secret_key', ''));
    $allowUrlFopen = strtolower(trim((string) ini_get('allow_url_fopen')));
    $hasTransport = function_exists('curl_init') || in_array($allowUrlFopen, ['1', 'on', 'true'], true);

    return $secretKey !== '' && $hasTransport;
}
