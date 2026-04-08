<?php

declare(strict_types=1);

final class AuthController
{
    private const BROWSER_LOGIN_WINDOW_SECONDS = 900;
    private const BROWSER_LOGIN_MAX_ATTEMPTS = 10;
    private const DUMMY_PASSWORD_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function register(): string
    {
        Auth::guestOnly();

        if (isPostRequest()) {
            Csrf::verifyRequest();

            $input = Validator::trim($_POST);
            $input['full_name'] = normalize_whitespace((string) ($input['full_name'] ?? ''));
            $input['email'] = mb_strtolower((string) ($input['email'] ?? ''));
            $input['role'] = trim((string) ($input['role'] ?? ''));

            Session::setOldInput([
                'full_name' => (string) $input['full_name'],
                'email' => (string) $input['email'],
                'role' => (string) $input['role'],
            ]);

            $fieldErrors = Validator::registrationFields($input);

            if ($this->users->findByEmail((string) $input['email']) !== null) {
                $fieldErrors['email'][] = 'An account with that email already exists.';
            }

            if ($fieldErrors !== []) {
                return $this->renderRegister(Validator::flattenFieldErrors($fieldErrors), $fieldErrors);
            }

            $this->users->createWithProfile([
                'create_trial_subscription' => true,
                'email' => (string) $input['email'],
                'password_hash' => password_hash((string) $input['password'], PASSWORD_DEFAULT),
                'role' => (string) $input['role'],
                'full_name' => (string) $input['full_name'],
            ]);

            Session::clearOldInput();
            Session::flash('success', 'Account created successfully. Please log in.');
            redirect('auth/login');
        }

        Session::clearOldInput();

        return $this->renderRegister();
    }

    public function login(): string
    {
        Auth::guestOnly();

        if (isPostRequest()) {
            Csrf::verifyRequest();

            $input = Validator::trim($_POST);
            $input['email'] = mb_strtolower((string) ($input['email'] ?? ''));

            Session::setOldInput([
                'email' => (string) $input['email'],
            ]);

            $fieldErrors = Validator::loginFields($input);

            if ($fieldErrors !== []) {
                return $this->renderLogin(Validator::flattenFieldErrors($fieldErrors), $fieldErrors);
            }

            if ($this->isBrowserLoginRateLimited()) {
                $this->absorbPasswordVerificationCost((string) ($input['password'] ?? ''));

                return $this->renderGenericLoginFailure();
            }

            $user = $this->users->findByEmail((string) $input['email']);

            if (
                $user === null
                || !in_array((string) $user['role'], ['client', 'tasker', 'admin'], true)
                || !(bool) $user['is_active']
            ) {
                $this->recordBrowserLoginAttempt();
                $this->absorbPasswordVerificationCost((string) ($input['password'] ?? ''));

                return $this->renderGenericLoginFailure();
            }

            if ($this->users->isLockedOut($user)) {
                $this->recordBrowserLoginAttempt();
                $this->absorbPasswordVerificationCost((string) ($input['password'] ?? ''));

                return $this->renderGenericLoginFailure();
            }

            if (!password_verify((string) $input['password'], (string) $user['password_hash'])) {
                $this->users->recordFailedLogin((int) $user['id']);
                $this->recordBrowserLoginAttempt();

                return $this->renderGenericLoginFailure();
            }

            $this->users->resetLoginAttempts((int) $user['id']);
            $this->clearBrowserLoginAttempts();
            Auth::login($user);
            Session::clearOldInput();
            Session::flash('success', 'You are now logged in.');
            redirect('home/index');
        }

        Session::clearOldInput();

        return $this->renderLogin();
    }

    public function logout(): string
    {
        verifyPostRequest('home/index');

        $logoutReason = trim((string) ($_POST['logout_reason'] ?? ''));
        Auth::logout();
        Session::start();

        if ($logoutReason === 'idle_timeout') {
            Session::flash('success', 'You were logged out after a long period of inactivity.');
        } else {
            Session::flash('success', 'You have been logged out.');
        }

        redirect('home/index');
    }

    public function ping(): string
    {
        header('Content-Type: application/json');

        if (!Auth::check()) {
            http_response_code(401);

            return json_encode([
                'authenticated' => false,
            ], JSON_THROW_ON_ERROR);
        }

        return json_encode([
            'authenticated' => true,
            'idle_timeout_seconds' => session_idle_timeout_seconds(),
            'presence_window_seconds' => session_presence_window_seconds(),
            'server_time' => date(DATE_ATOM),
        ], JSON_THROW_ON_ERROR);
    }

    private function renderRegister(array $errors = [], array $fieldErrors = []): string
    {
        return View::render('auth/register', [
            'pageTitle' => 'Register',
            'errors' => $errors,
            'fieldErrors' => $fieldErrors,
        ]);
    }

    private function renderLogin(array $errors = [], array $fieldErrors = []): string
    {
        return View::render('auth/login', [
            'pageTitle' => 'Login',
            'errors' => $errors,
            'fieldErrors' => $fieldErrors,
        ]);
    }

    private function renderGenericLoginFailure(): string
    {
        return $this->renderLogin([
            'We couldn\'t sign you in with those details. Please check your email and password, and if you have tried several times, wait a few minutes before trying again.',
        ]);
    }

    private function absorbPasswordVerificationCost(string $password): void
    {
        password_verify($password, self::DUMMY_PASSWORD_HASH);
    }

    private function isBrowserLoginRateLimited(): bool
    {
        return count($this->currentBrowserLoginAttempts()) >= self::BROWSER_LOGIN_MAX_ATTEMPTS;
    }

    private function recordBrowserLoginAttempt(): void
    {
        $attempts = $this->currentBrowserLoginAttempts();
        $attempts[] = time();
        Session::put('_login_attempts', $attempts);
    }

    private function clearBrowserLoginAttempts(): void
    {
        Session::forget('_login_attempts');
    }

    private function currentBrowserLoginAttempts(): array
    {
        $cutoff = time() - self::BROWSER_LOGIN_WINDOW_SECONDS;
        $attempts = array_values(array_filter(
            (array) Session::get('_login_attempts', []),
            static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff
        ));

        Session::put('_login_attempts', $attempts);

        return $attempts;
    }
}
