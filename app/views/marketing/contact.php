<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$contact = is_array($contact ?? null) ? $contact : app_config('contact', []);
$currentUser = Auth::user();
?>
<div class="container">
    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">Contact</span>
            <h1>Reach out without guessing where support lives.</h1>
            <p class="page-intro">Use this form for pricing questions, partnership conversations, support follow-up, or early-launch coordination. The form includes validation and a simple spam honeypot so it can work today without a third-party service.</p>
        </div>
    </section>

    <div class="contact-layout">
        <section class="panel">
            <?php
            $title = 'Send a message';
            $eyebrow = 'Support form';
            $intro = 'Keep it concise. We only need enough detail to know who should respond and what the next step is.';
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <form method="post" action="<?= e(url_for('marketing/contact-submit')) ?>" class="form-grid" novalidate>
                <?= Csrf::input() ?>

                <div class="sr-only" aria-hidden="true">
                    <label for="contact-website">Website</label>
                    <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid-2">
                    <?php
                    $name = 'name';
                    $label = 'Name';
                    $type = 'text';
                    $value = old_value('name', (string) ($currentUser['full_name'] ?? ''));
                    $placeholder = 'Aline Uwase';
                    $autocomplete = 'name';
                    $required = true;
                    $hint = null;
                    $error = $fieldErrors['name'][0] ?? null;
                    $attrs = ['maxlength' => '150'];
                    require BASE_PATH . '/app/views/partials/form_field.php';
                    ?>

                    <?php
                    $name = 'email';
                    $label = 'Email';
                    $type = 'email';
                    $value = old_value('email', (string) ($currentUser['email'] ?? ''));
                    $placeholder = 'you@example.com';
                    $autocomplete = 'email';
                    $required = true;
                    $hint = null;
                    $error = $fieldErrors['email'][0] ?? null;
                    $attrs = ['inputmode' => 'email'];
                    require BASE_PATH . '/app/views/partials/form_field.php';
                    ?>
                </div>

                <div class="grid-2">
                    <?php
                    $name = 'company';
                    $label = 'Company or team';
                    $type = 'text';
                    $value = old_value('company');
                    $placeholder = 'Optional';
                    $autocomplete = 'organization';
                    $required = false;
                    $hint = 'Optional, but helpful for partnership or office requests.';
                    $error = $fieldErrors['company'][0] ?? null;
                    $attrs = ['maxlength' => '150'];
                    require BASE_PATH . '/app/views/partials/form_field.php';
                    ?>

                    <?php
                    $name = 'topic';
                    $label = 'Topic';
                    $type = 'text';
                    $value = old_value('topic');
                    $placeholder = 'Pricing question, support request, partnership';
                    $autocomplete = 'off';
                    $required = true;
                    $hint = null;
                    $error = $fieldErrors['topic'][0] ?? null;
                    $attrs = ['maxlength' => '120'];
                    require BASE_PATH . '/app/views/partials/form_field.php';
                    ?>
                </div>

                <?php
                $name = 'message';
                $label = 'Message';
                $as = 'textarea';
                $value = old_value('message');
                $placeholder = 'Tell us what you need, what plan you are considering, or what issue you are trying to solve.';
                $autocomplete = 'off';
                $required = true;
                $hint = 'Please avoid sharing sensitive payment details in the message itself.';
                $error = $fieldErrors['message'][0] ?? null;
                $attrs = ['maxlength' => '3000'];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <div class="form-actions">
                    <button type="submit" class="button">Send message</button>
                </div>
            </form>
        </section>

        <aside class="panel panel-subtle contact-side">
            <div class="section-stack">
                <div>
                    <span class="eyebrow">Direct details</span>
                    <h2>Prefer direct contact?</h2>
                </div>
                <ul class="meta-list">
                    <li>
                        <strong>Email</strong>
                        <span><a href="mailto:<?= e((string) ($contact['email'] ?? 'hello@yourdomain.com')) ?>"><?= e((string) ($contact['email'] ?? 'hello@yourdomain.com')) ?></a></span>
                    </li>
                    <li>
                        <strong>Phone</strong>
                        <span><?= e((string) ($contact['phone'] ?? '+250 000 000 000')) ?></span>
                    </li>
                    <li>
                        <strong>Location</strong>
                        <span><?= e((string) ($contact['location'] ?? 'Kigali, Rwanda')) ?></span>
                    </li>
                    <li>
                        <strong>Hours</strong>
                        <span><?= e((string) ($contact['hours'] ?? 'Mon-Fri, 08:00-18:00 CAT')) ?></span>
                    </li>
                </ul>

                <div class="support-note">
                    <h3>What happens next</h3>
                    <p class="muted">Right now messages are stored locally as a launch-phase stub. It is enough to test the UX end to end and swap in a real email/helpdesk integration later.</p>
                </div>
            </div>
        </aside>
    </div>
</div>
