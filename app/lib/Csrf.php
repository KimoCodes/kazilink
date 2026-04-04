<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf_token');

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf_token', $token);
        }

        return $token;
    }

    public static function input(): string
    {
        $name = (string) app_config('csrf_token_name', '_token');
        $value = e(self::token());

        return '<input type="hidden" name="' . e($name) . '" value="' . $value . '">';
    }

    public static function verifyRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $name = (string) app_config('csrf_token_name', '_token');
        $submitted = $_POST[$name] ?? '';
        $sessionToken = Session::get('_csrf_token', '');

        if (!is_string($submitted) || !is_string($sessionToken) || $submitted === '' || !hash_equals($sessionToken, $submitted)) {
            http_response_code(419);
            Session::flash('error', 'Your session expired. Please try again.');
            redirect('home/index');
        }
    }
}
