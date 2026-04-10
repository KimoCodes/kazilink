<?php

declare(strict_types=1);

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Set secure session parameters
        session_name((string) app_config('session_name', 'informal_session'));
        
        // Enhanced cookie security
        $cookieParams = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ];
        
        session_set_cookie_params($cookieParams);
        
        // Set additional session security settings
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
        
        $sessionPath = BASE_PATH . '/storage/sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }
        
        if (ini_get('session.save_handler') === 'files') {
            ini_set('session.save_path', $sessionPath);
        }
        
        session_start();
        
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['_session_created_at'])) {
            $_SESSION['_session_created_at'] = time();
            $_SESSION['_session_last_regenerated'] = time();
        } elseif (time() - $_SESSION['_session_last_regenerated'] > 300) { // Regenerate every 5 minutes
            self::regenerate();
            $_SESSION['_session_last_regenerated'] = time();
        }
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    public static function getFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($messages) ? $messages : [];
    }

    public static function setOldInput(array $input): void
    {
        $_SESSION['_old_input'] = $input;
    }

    public static function getOldInput(string $key, mixed $default = null): mixed
    {
        $input = $_SESSION['_old_input'] ?? [];

        return $input[$key] ?? $default;
    }

    public static function clearOldInput(): void
    {
        unset($_SESSION['_old_input']);
    }

    public static function invalidate(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}
