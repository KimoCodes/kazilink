<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
?>
<div class="container">
    <div class="auth-shell-wrap">
        <article class="auth-card">
            <div class="auth-masthead">
                <span class="eyebrow">Sign In</span>
                <h1>Welcome back</h1>
                <p class="page-intro">Manage tasks, bookings, and messages from one professional workspace built for Rwanda-based service coordination.</p>
            </div>

            <form method="post" action="<?= e(url_for('auth/login')) ?>" class="form-grid" novalidate>
                <?= Csrf::input() ?>

                <?php
                $name = 'email';
                $label = 'Email address';
                $as = 'input';
                $type = 'email';
                $value = old_value('email');
                $placeholder = 'you@example.com';
                $autocomplete = 'email';
                $required = true;
                $hint = null;
                $error = $fieldErrors['email'][0] ?? null;
                $attrs = ['inputmode' => 'email'];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <?php
                $name = 'password';
                $label = 'Password';
                $as = 'input';
                $type = 'password';
                $value = '';
                $placeholder = 'Enter your password';
                $autocomplete = 'current-password';
                $required = true;
                $hint = null;
                $error = $fieldErrors['password'][0] ?? null;
                $attrs = ['minlength' => '8'];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <div class="form-actions auth-form-actions">
                    <button type="submit" class="button button-block">Sign in</button>
                </div>
            </form>

            <div class="auth-divider" aria-hidden="true">
                <span>New to Informal?</span>
            </div>

            <a class="button button-secondary button-block" href="<?= e(url_for('auth/register')) ?>">Create a new account</a>
        </article>

        <aside class="auth-side">
            <div class="auth-side-copy">
                <span class="eyebrow">Marketplace access</span>
                <h2>Keep every booking conversation in one place</h2>
                <p>Sign in to review bids, confirm task progress, and respond quickly without losing context between tasks and messages.</p>
            </div>

            <div class="auth-proof-list">
                <section class="auth-proof-card">
                    <h3>Clear task tracking</h3>
                    <p>Move between open work, active bookings, and completed jobs with consistent status updates.</p>
                </section>
                <section class="auth-proof-card">
                    <h3>Secure session handling</h3>
                    <p>Sessions are protected with CSRF checks, server-side validation, and safe sign-in handling.</p>
                </section>
                <section class="auth-proof-card">
                    <h3>Made for Rwanda</h3>
                    <p>Task budgets, locations, and scheduling language stay aligned with Kigali and wider Rwanda use cases.</p>
                </section>
            </div>

            <div class="auth-callout">
                <p class="auth-callout-title">Demo sign-in</p>
                <div class="auth-demo-list">
                    <code>client@example.com / password123</code>
                    <code>tasker@example.com / password123</code>
                </div>
            </div>
        </aside>
    </div>
</div>
