<?php
$plans = is_array($plans ?? null) ? $plans : array_values(pricing_plans());
$paymentsEnabled = (bool) ($paymentsEnabled ?? payments_enabled());
?>
<div class="container">
    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">Pricing</span>
            <h1>Simple one-time plans with a secure payment handoff.</h1>
            <p class="page-intro">Each plan is intentionally concise. The goal is to help people choose quickly, pay securely through Stripe Checkout, and understand what happens after payment.</p>
        </div>
    </section>

    <section class="panel panel-subtle">
        <div class="section-head">
            <div>
                <span class="eyebrow">Choose a plan</span>
                <h2>Professional defaults for households, repeat clients, and teams</h2>
            </div>
        </div>
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
                <article class="pricing-card <?= !empty($plan['highlighted']) ? 'pricing-card-featured' : '' ?>">
                    <div class="pricing-card-head">
                        <div>
                            <span class="pricing-badge"><?= e((string) ($plan['badge'] ?? 'Plan')) ?></span>
                            <h3><?= e((string) $plan['name']) ?></h3>
                            <p class="muted"><?= e((string) $plan['description']) ?></p>
                        </div>
                        <div class="pricing-amount">
                            <strong><?= e(moneyRwf($plan['amount'])) ?></strong>
                            <span><?= e((string) ($plan['billing_label'] ?? 'One-time payment')) ?></span>
                        </div>
                    </div>

                    <ul class="check-list">
                        <?php foreach ((array) ($plan['features'] ?? []) as $feature): ?>
                            <li><?= e((string) $feature) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($paymentsEnabled): ?>
                        <form method="post" action="<?= e(url_for('payments/checkout')) ?>" class="pricing-form">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>">
                            <button type="submit" class="button button-block"><?= e((string) ($plan['cta'] ?? 'Pay now')) ?></button>
                        </form>
                    <?php else: ?>
                        <div class="setup-note">
                            <strong>Payments are not configured yet.</strong>
                            <p class="muted">Add `STRIPE_SECRET_KEY` and related env values to enable checkout in this workspace.</p>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">What this includes</span>
                <h2>The payment path is intentionally lightweight</h2>
            </div>
        </div>
        <div class="marketing-grid marketing-grid-three">
            <article class="info-card">
                <h3>Hosted card entry</h3>
                <p class="muted">Sensitive card details are handled by Stripe Checkout rather than custom in-app inputs.</p>
            </article>
            <article class="info-card">
                <h3>Environment-based configuration</h3>
                <p class="muted">Keys and URLs live in `.env`, keeping payment settings out of the views themselves.</p>
            </article>
            <article class="info-card">
                <h3>Success and cancel states</h3>
                <p class="muted">Users are brought back to clear post-payment pages instead of ending in an unclear external dead end.</p>
            </article>
        </div>
    </section>
</div>
