<?php

declare(strict_types=1);

final class Auth
{
    private static bool $loggedOutForInactiveAccount = false;

    public static function user(): ?array
    {
        $sessionUser = Session::get('auth_user');

        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return null;
        }

        $freshUser = (new User())->findById((int) $sessionUser['id']);

        if ($freshUser === null || !(bool) $freshUser['is_active']) {
            self::$loggedOutForInactiveAccount = true;
            Session::invalidate();
            return null;
        }

        $normalizedUser = [
            'id' => (int) $freshUser['id'],
            'email' => (string) $freshUser['email'],
            'role' => (string) $freshUser['role'],
            'full_name' => (string) ($freshUser['full_name'] ?? ''),
        ];

        Session::put('auth_user', $normalizedUser);

        return $normalizedUser;
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
        Session::put('auth_user', [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'full_name' => (string) ($user['full_name'] ?? ''),
        ]);
    }

    public static function logout(): void
    {
        Session::invalidate();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (self::$loggedOutForInactiveAccount) {
                Session::start();
                Session::flash('error', 'Your session has ended because your account is inactive. Please log in again.');
                self::$loggedOutForInactiveAccount = false;
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
}
