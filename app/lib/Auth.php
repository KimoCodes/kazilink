<?php

declare(strict_types=1);

final class Auth
{
    private static bool $loggedOutForInactiveAccount = false;
    private static bool $loggedOutForIdleSession = false;
    private static ?array $resolvedUser = null;
    private static bool $hasResolvedUser = false;

    public static function user(): ?array
    {
        if (self::$hasResolvedUser) {
            return self::$resolvedUser;
        }

        $sessionUser = Session::get('auth_user');

        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            self::$resolvedUser = null;
            self::$hasResolvedUser = true;

            return null;
        }

        $userId = (int) $sessionUser['id'];

        if (self::hasExpiredIdleSession()) {
            (new User())->recordLogout($userId);
            self::$loggedOutForIdleSession = true;
            Session::invalidate();
            self::$resolvedUser = null;
            self::$hasResolvedUser = true;

            Logger::logAuthEvent('session_expired_idle', [
                'user_id' => $userId,
                'last_activity' => Session::get('last_activity_at'),
            ]);

            return null;
        }

        $freshUser = (new User())->findById($userId);

        if ($freshUser === null) {
            Logger::logAuthEvent('session_invalid_user', [
                'user_id' => $userId,
            ]);
            
            (new User())->recordLogout($userId);
            self::$loggedOutForInactiveAccount = true;
            Session::invalidate();

            self::$resolvedUser = null;
            self::$hasResolvedUser = true;

            return null;
        }

        if (!(bool) $freshUser['is_active']) {
            Logger::logAuthEvent('session_inactive_account', [
                'user_id' => $userId,
                'email' => (string) ($freshUser['email'] ?? ''),
            ]);
            
            (new User())->recordLogout($userId);
            self::$loggedOutForInactiveAccount = true;
            Session::invalidate();

            self::$resolvedUser = null;
            self::$hasResolvedUser = true;

            return null;
        }

        $normalizedUser = [
            'id' => (int) $freshUser['id'],
            'email' => (string) $freshUser['email'],
            'role' => (string) $freshUser['role'],
            'full_name' => (string) ($freshUser['full_name'] ?? ''),
        ];

        Session::put('auth_user', $normalizedUser);
        self::touchSessionActivity($userId);
        self::$resolvedUser = $normalizedUser;
        self::$hasResolvedUser = true;

        return self::$resolvedUser;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return isset($user['id']) ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();

        return $user['role'] ?? null;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        self::$loggedOutForInactiveAccount = false;
        self::$loggedOutForIdleSession = false;
        
        $now = time();
        $normalizedUser = [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'full_name' => (string) ($user['full_name'] ?? ''),
        ];
        
        // Set session data immediately
        Session::put('auth_user', $normalizedUser);
        Session::put('last_activity_at', $now);
        Session::put('last_presence_write_at', 0);
        
        // Update cached user data
        self::$resolvedUser = $normalizedUser;
        self::$hasResolvedUser = true;
        
        // Update user presence in database
        (new User())->touchPresence((int) $user['id']);
        
        Logger::logAuthEvent('login_success', [
            'user_id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ]);
    }

    public static function logout(): void
    {
        $sessionUser = Session::get('auth_user');

        if (is_array($sessionUser) && isset($sessionUser['id'])) {
            (new User())->recordLogout((int) $sessionUser['id']);
            
            Logger::logAuthEvent('logout', [
                'user_id' => (int) $sessionUser['id'],
                'email' => (string) ($sessionUser['email'] ?? ''),
                'role' => (string) ($sessionUser['role'] ?? ''),
            ]);
        }

        Session::invalidate();
        self::$resolvedUser = null;
        self::$hasResolvedUser = true;
        self::$loggedOutForInactiveAccount = false;
        self::$loggedOutForIdleSession = false;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (self::$loggedOutForInactiveAccount) {
                Session::start();
                Session::flash('error', 'Your session has ended because your account is inactive. Please log in again.');
                self::$loggedOutForInactiveAccount = false;
            } elseif (self::$loggedOutForIdleSession) {
                Session::start();
                Session::flash('error', 'Your session ended after being inactive for too long. Please log in again.');
                self::$loggedOutForIdleSession = false;
            } else {
                Session::flash('error', 'Please log in to continue.');
            }

            redirect('auth/login');
        }
    }

    public static function requireRole(array|string $roles): void
    {
        self::requireLogin();

        $roles = is_array($roles) ? $roles : [$roles];

        if (!in_array((string) self::role(), $roles, true)) {
            http_response_code(403);
            Session::flash('error', 'You do not have permission to access that page.');
            redirect('home/index');
        }
    }

    public static function guestOnly(): void
    {
        if (self::check()) {
            redirect('home/index');
        }
    }

    private static function hasExpiredIdleSession(): bool
    {
        $lastActivityAt = Session::get('last_activity_at');

        // If no activity timestamp is set, don't consider it expired
        // This handles the case right after login
        if (!is_int($lastActivityAt)) {
            return false;
        }

        $timeoutSeconds = session_idle_timeout_seconds();
        return $lastActivityAt < (time() - $timeoutSeconds);
    }

    private static function touchSessionActivity(int $userId): void
    {
        $now = time();
        Session::put('last_activity_at', $now);

        $lastPresenceWriteAt = Session::get('last_presence_write_at');

        if (!is_int($lastPresenceWriteAt) || $lastPresenceWriteAt <= ($now - session_heartbeat_interval_seconds())) {
            (new User())->touchPresence($userId);
            Session::put('last_presence_write_at', $now);
        }
    }
}
