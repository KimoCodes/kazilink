<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
$roleOptions = [
    ['value' => '', 'label' => 'Select your role'],
    ['value' => 'client', 'label' => 'Client - Post tasks and hire taskers'],
    ['value' => 'tasker', 'label' => 'Tasker - Browse work and send bids'],
];
?>
<div class="container">
    <div class="auth-shell-wrap">
        <article class="auth-card">
            <div class="auth-masthead">
                <span class="eyebrow">Create Account</span>
                <h1>Join with the role that fits your work</h1>
                <p class="page-intro">Set up a client or tasker account with clear details so you can start posting work or bidding with confidence.</p>
            </div>

            <form method="post" action="<?= e(url_for('auth/register')) ?>" class="form-grid" novalidate>
                <?= Csrf::input() ?>

                <?php
                $name = 'full_name';
                $label = 'Full name';
                $as = 'input';
                $type = 'text';
                $value = old_value('full_name');
                $placeholder = 'Aline Uwase';
                $autocomplete = 'name';
                $required = true;
                $hint = 'Use the name you want clients and taskers to see on your profile.';
                $error = $fieldErrors['full_name'][0] ?? null;
                $attrs = ['maxlength' => '150'];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <?php
                $name = 'email';
                $label = 'Email address';
                $as = 'input';
                $type = 'email';
                $value = old_value('email');
                $placeholder = 'you@example.com';
                $autocomplete = 'email';
                $required = true;
                $hint = 'Booking updates and message alerts will go to this address.';
                $error = $fieldErrors['email'][0] ?? null;
                $attrs = ['inputmode' => 'email'];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <?php
                $name = 'role';
                $label = 'Primary role';
                $as = 'select';
                $type = 'text';
                $value = old_value('role');
                $required = true;
                $placeholder = null;
                $autocomplete = null;
                $hint = 'Use separate accounts if you need distinct client and tasker identities.';
                $error = $fieldErrors['role'][0] ?? null;
                $options = $roleOptions;
                $attrs = [];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <?php
                $name = 'password';
                $label = 'Password';
                $as = 'input';
                $type = 'password';
                $value = '';
                $placeholder = 'At least 8 characters';
                $autocomplete = 'new-password';
                $required = true;
                $hint = 'Choose a strong password that you do not reuse elsewhere.';
                $error = $fieldErrors['password'][0] ?? null;
                $attrs = ['minlength' => '8'];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <div class="form-actions auth-form-actions">
                    <button type="submit" class="button button-block">Create account</button>
                </div>
            </form>

            <div class="auth-divider" aria-hidden="true">
                <span>Already have an account?</span>
            </div>

            <a class="button button-secondary button-block" href="<?= e(url_for('auth/login')) ?>">Sign in instead</a>
        </article>

        <aside class="auth-side">
            <div class="auth-side-copy">
                <span class="eyebrow">Role guidance</span>
                <h2>Pick the account that matches how you use the marketplace</h2>
                <p>The experience stays simple: clients post clear jobs, while taskers browse opportunities and respond with one focused bid per task.</p>
            </div>

            <div class="auth-role-list">
                <section class="auth-role-card">
                    <h3>Client</h3>
                    <p>Best when you need reliable help for cleaning, delivery, repairs, errands, or home support.</p>
                    <ul class="auth-checklist">
                        <li>Post tasks with budgets in RWF</li>
                        <li>Compare bids before accepting one</li>
                        <li>Message taskers inside each booking</li>
                    </ul>
                </section>

                <section class="auth-role-card auth-role-card-featured">
                    <span class="auth-role-badge">Popular</span>
                    <h3>Tasker</h3>
                    <p>Best when you want to browse open work, send bids, and build trust through completed jobs and reviews.</p>
                    <ul class="auth-checklist">
                        <li>Browse new tasks quickly</li>
                        <li>Submit one clear bid per task</li>
                        <li>Keep accepted work organized</li>
                    </ul>
                </section>
            </div>

            <div class="auth-callout auth-callout-success">
                <p class="auth-callout-title">Built for local trust</p>
                <p>Use accurate names, realistic budgets, and Rwanda-friendly location details to help the right people respond faster.</p>
            </div>
        </aside>
    </div>
</div>
