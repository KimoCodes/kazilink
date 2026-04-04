<?php
$checkoutSession = is_array($checkoutSession ?? null) ? $checkoutSession : null;
$localPayment = is_array($localPayment ?? null) ? $localPayment : null;
$paymentStatus = (string) ($checkoutSession['payment_status'] ?? 'paid');
$customerEmail = (string) ($checkoutSession['customer_details']['email'] ?? $checkoutSession['customer_email'] ?? '');
$planName = (string) ($checkoutSession['metadata']['plan_name'] ?? 'Selected plan');
$reference = (string) ($checkoutSession['id'] ?? ($_GET['session_id'] ?? ''));
?>
<div class="container narrow">
    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">Payment success</span>
            <h1>Your payment has been received.</h1>
            <p class="page-intro">The checkout flow completed successfully. This page can verify the Stripe session when keys are configured and a session ID is available.</p>
        </div>
    </section>

    <section class="panel">
        <ul class="summary-list">
            <li>
                <strong>Plan</strong>
                <span><?= e($planName) ?></span>
            </li>
            <li>
                <strong>Status</strong>
                <span><?= e(ucfirst($paymentStatus)) ?></span>
            </li>
            <?php if ($localPayment !== null): ?>
                <li>
                    <strong>Local record</strong>
                    <span><?= e(ucfirst((string) $localPayment['status'])) ?></span>
                </li>
            <?php endif; ?>
            <li>
                <strong>Receipt email</strong>
                <span><?= e($customerEmail !== '' ? $customerEmail : 'Available in Stripe Checkout details') ?></span>
            </li>
            <li>
                <strong>Reference</strong>
                <span class="text-mono"><?= e($reference !== '' ? $reference : 'Not available') ?></span>
            </li>
        </ul>

        <?php if (!empty($checkoutError)): ?>
            <p class="muted payment-note"><?= e((string) $checkoutError) ?></p>
        <?php endif; ?>

        <div class="hero-actions payment-actions">
            <?php if ($localPayment !== null && (int) ($localPayment['booking_id'] ?? 0) > 0): ?>
                <a class="button" href="<?= e(url_for('bookings/show', ['id' => (int) $localPayment['booking_id']])) ?>">Open booking</a>
            <?php endif; ?>
            <a class="button" href="<?= e(url_for('marketing/contact')) ?>">Contact support</a>
            <a class="button button-secondary" href="<?= e(url_for('home/index')) ?>">Return home</a>
        </div>
    </section>
</div>
