<div class="container">
    <?php $subscriptionSummary = is_array($subscriptionSummary ?? null) ? $subscriptionSummary : []; ?>
    <?php $plans = is_array($plans ?? null) ? $plans : []; ?>
    <?php $statusTransaction = is_array($statusTransaction ?? null) ? $statusTransaction : null; ?>

    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">Subscription Access</span>
            <h1>Keep job payments offline. Pay platform subscriptions with MTN MoMo.</h1>
            <p class="page-intro">Every new user starts with a 30-day free Basic trial. After that, paid access keeps posting, bidding, and marketplace selling turned on while higher tiers improve discovery visibility.</p>
        </div>
    </section>

    <section class="panel panel-subtle">
        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Current plan</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) ($subscriptionSummary['plan_name'] ?? 'Basic')) ?></strong>
                    <span>Priority <?= e((string) ($subscriptionSummary['priority_level'] ?? 1)) ?> • Visibility level <?= e((string) ($subscriptionSummary['visibility_level'] ?? 1)) ?></span>
                </div>
                <?php if (!empty($subscriptionSummary['badge_name'])): ?>
                    <span class="subscription-tier-badge" style="margin-top: var(--space-3);"><?= e((string) $subscriptionSummary['badge_name']) ?></span>
                <?php endif; ?>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Access status</span>
                <div class="task-summary-metric-row">
                    <strong><?= e(ucfirst(str_replace('_', ' ', (string) ($subscriptionSummary['status'] ?? 'trialing')))) ?></strong>
                    <span>
                        <?php if (!empty($subscriptionSummary['trial_ends_at'])): ?>
                            Trial ends <?= e(dateFmt((string) $subscriptionSummary['trial_ends_at'])) ?>
                        <?php elseif (!empty($subscriptionSummary['current_period_ends_at'])): ?>
                            Paid through <?= e(dateFmt((string) $subscriptionSummary['current_period_ends_at'])) ?>
                        <?php else: ?>
                            Billing dates will appear after activation.
                        <?php endif; ?>
                    </span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Grace period</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) ($subscriptionSummary['grace_days'] ?? 0)) ?> day<?= (int) ($subscriptionSummary['grace_days'] ?? 0) !== 1 ? 's' : '' ?></strong>
                    <span><?= !empty($subscriptionSummary['grace_ends_at']) ? 'Ends ' . e(dateFmt((string) $subscriptionSummary['grace_ends_at'])) : 'Applied after a paid period expires.' ?></span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Performance boosts</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) ($subscriptionSummary['max_applications_per_day'] ?? 0)) ?>/day</strong>
                    <span><?= e((string) ($subscriptionSummary['max_active_jobs'] ?? 0)) ?> active job<?= (int) ($subscriptionSummary['max_active_jobs'] ?? 0) === 1 ? '' : 's' ?> • <?= e((string) ($subscriptionSummary['job_alert_delay_minutes'] ?? 0)) ?> min visibility timing</span>
                </div>
                <?php if ((float) ($subscriptionSummary['commission_discount'] ?? 0) > 0): ?>
                    <p class="muted" style="margin: var(--space-3) 0 0;">Commission discount: <?= e(number_format((float) $subscriptionSummary['commission_discount'], 0)) ?>%</p>
                <?php endif; ?>
            </article>
        </div>
    </section>

    <?php if ($statusTransaction !== null): ?>
        <section class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Payment Status</span>
                    <h2>MTN MoMo request</h2>
                </div>
            </div>
            <div class="info-card" <?= (string) $statusTransaction['status'] === 'pending' ? 'data-subscription-status data-status-url="' . e(url_for('subscriptions/poll', ['ref' => $statusRef])) . '"' : '' ?>>
                <p><strong>Reference:</strong> <span class="text-mono"><?= e((string) $statusRef) ?></span></p>
                <p><strong>Current state:</strong> <span data-subscription-status-text><?= e((string) $statusTransaction['status']) ?></span></p>
                <p class="muted">Keep this page open after approving the request on the phone. We will poll for status updates automatically.</p>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Choose a plan</span>
                <h2>Monthly plans</h2>
            </div>
        </div>
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
                <article class="pricing-card <?= (int) ($currentPlanId ?? 0) === (int) $plan['id'] ? 'pricing-card-featured' : '' ?>">
                    <div class="pricing-card-head">
                        <div>
                            <h3><?= e((string) $plan['name']) ?></h3>
                            <span class="pricing-badge">Priority <?= e((string) $plan['priority_level']) ?></span>
                            <?php if (!empty($plan['badge_name'])): ?>
                                <div><span class="subscription-tier-badge"><?= e((string) $plan['badge_name']) ?></span></div>
                            <?php endif; ?>
                        </div>
                        <div class="pricing-amount">
                            <strong><?= e(moneyRwf((int) $plan['price_rwf'])) ?></strong>
                            <span>per month</span>
                        </div>
                    </div>
                    <ul class="check-list">
                        <li><?= e((string) $plan['max_applications_per_day']) ?> applications per day</li>
                        <li><?= e((string) $plan['max_active_jobs']) ?> active client jobs</li>
                        <li><?= e((string) $plan['job_alert_delay_minutes']) ?> minute visibility timing</li>
                        <li>Search priority level <?= e((string) $plan['priority_level']) ?></li>
                        <li><?= (float) $plan['commission_discount'] > 0 ? e(number_format((float) $plan['commission_discount'], 0)) . '% commission discount' : 'Standard commission' ?></li>
                    </ul>
                    <form method="post" action="<?= e(url_for('subscriptions/subscribe')) ?>" class="stack-form">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>">
                        <label>
                            <span>MTN MoMo phone</span>
                            <input type="text" name="phone" placeholder="2507XXXXXXXX">
                        </label>
                        <label>
                            <span>Promo code</span>
                            <input type="text" name="promo_code" placeholder="Optional">
                        </label>
                        <button type="submit" class="button"><?= (int) ($currentPlanId ?? 0) === (int) $plan['id'] ? 'Renew this plan' : 'Upgrade to this plan' ?></button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
