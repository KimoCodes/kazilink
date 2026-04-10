<div class="container">
    <?php $subscriptionSummary = is_array($subscriptionSummary ?? null) ? $subscriptionSummary : []; ?>
    <?php $plans = is_array($plans ?? null) ? $plans : []; ?>
    <?php $selectedPaymentIntent = is_array($selectedPaymentIntent ?? null) ? $selectedPaymentIntent : null; ?>
    <?php $recentPaymentIntents = is_array($recentPaymentIntents ?? null) ? $recentPaymentIntents : []; ?>

    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">Subscription Access</span>
            <h1>Keep job payments offline. Pay platform subscriptions with MTN MoMo.</h1>
            <p class="page-intro">Every new user starts with a 30-day free Basic trial. Paid plans are activated only after a manual MTN MoMo screenshot review, and proof must be submitted at least 2 days before activation.</p>
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

    <?php if ($selectedPaymentIntent !== null): ?>
        <section class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Pay with MTN MoMo</span>
                    <h2>Payment Instructions</h2>
                </div>
            </div>
            <div class="summary-grid booking-index-summary">
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Plan</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) ($selectedPaymentIntent['plan_name'] ?? 'Selected plan')) ?></strong>
                        <span>Send exactly <?= e(moneyRwf((int) ($selectedPaymentIntent['amount_expected_rwf'] ?? 0))) ?></span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">MTN MoMo number</span>
                    <div class="task-summary-metric-row">
                        <strong class="text-mono"><?= e((string) ($selectedPaymentIntent['momo_number_displayed'] ?? $momoDisplayNumber ?? '')) ?></strong>
                        <span>Use this exact payee number.</span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Intended activation</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e(dateFmt((string) ($selectedPaymentIntent['intended_activation_at'] ?? ''))) ?></strong>
                        <span>Your payment proof must be submitted 2 days before this time.</span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Payment deadline</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e(dateFmt((string) ($selectedPaymentIntent['deadline_at'] ?? ''))) ?></strong>
                        <span>Late submissions cannot be approved for activation.</span>
                    </div>
                </article>
            </div>

            <div class="info-card" style="margin-top: var(--space-4);">
                <p><strong>Status:</strong> <?= e(ucfirst(str_replace('_', ' ', (string) ($selectedPaymentIntent['status'] ?? 'draft')))) ?><?= (int) ($selectedPaymentIntent['is_late'] ?? 0) === 1 ? ' • Late' : '' ?></p>
                <?php if (!empty($selectedPaymentIntent['rejection_reason'])): ?>
                    <p><strong>Reason:</strong> <?= e((string) $selectedPaymentIntent['rejection_reason']) ?></p>
                <?php endif; ?>
                <?php if ((string) ($selectedPaymentIntent['status'] ?? '') === 'approved'): ?>
                    <p>Your payment has been verified successfully. Your plan will be activated on <?= e(dateFmt((string) ($selectedPaymentIntent['intended_activation_at'] ?? ''))) ?>.</p>
                <?php elseif ((string) ($selectedPaymentIntent['status'] ?? '') === 'pending_verification'): ?>
                    <p>Your payment proof has been received and is awaiting admin review. We will verify the amount, timing, and payment details before activating your plan.</p>
                <?php elseif ((string) ($selectedPaymentIntent['status'] ?? '') === 'rejected'): ?>
                    <p>We could not verify your payment proof. Please upload a clearer or correct screenshot and submit again before the deadline.</p>
                <?php endif; ?>
            </div>

            <?php if (in_array((string) ($selectedPaymentIntent['status'] ?? ''), ['draft', 'rejected'], true)): ?>
                <div class="section-head" style="margin-top: var(--space-5);">
                    <div>
                        <span class="eyebrow">Upload Payment Proof</span>
                        <h2>Submit for Verification</h2>
                        <p class="section-intro">Upload a clear screenshot showing the payment amount, transaction date, and phone details if visible.</p>
                    </div>
                </div>
                <form method="post" action="<?= e(url_for('subscriptions/submit-manual-payment')) ?>" enctype="multipart/form-data" class="stack-form">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="payment_intent_id" value="<?= e((string) $selectedPaymentIntent['id']) ?>">
                    <label>
                        <span>Payment screenshot</span>
                        <input type="file" name="screenshot" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                    </label>
                    <label>
                        <span>Payer phone number (optional)</span>
                        <input type="text" name="payer_phone" placeholder="2507XXXXXXXX" value="<?= old('payer_phone') ?>">
                    </label>
                    <label>
                        <span>Amount paid (optional)</span>
                        <input type="text" name="amount_paid" placeholder="10000" value="<?= old('amount_paid') ?>">
                    </label>
                    <button type="submit" class="button">Submit for Verification</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Choose Your Plan</span>
                <h2>Select a plan and review the payment details before continuing.</h2>
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
                            <span>Activation date and time</span>
                            <input type="datetime-local" name="intended_activation_at" min="<?= e((new DateTimeImmutable('now', new DateTimeZone((string) app_config('timezone', 'Africa/Kigali'))))->modify('+48 hours')->format('Y-m-d\TH:i')) ?>" required>
                        </label>
                        <button type="submit" class="button">Continue to Payment</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($recentPaymentIntents !== []): ?>
        <section class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Payment History</span>
                    <h2>Recent manual payment requests</h2>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Created</th><th>Plan</th><th>Activation</th><th class="hide-mobile">Deadline</th><th>Status</th><th>Open</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPaymentIntents as $intent): ?>
                            <tr>
                                <td><?= e(dateFmt((string) $intent['created_at'])) ?></td>
                                <td><?= e((string) ($intent['plan_name'] ?? 'Plan')) ?></td>
                                <td><?= e(dateFmt((string) ($intent['intended_activation_at'] ?? ''))) ?></td>
                                <td class="hide-mobile"><?= e(dateFmt((string) ($intent['deadline_at'] ?? ''))) ?></td>
                                <td>
                                    <?= e(ucfirst(str_replace('_', ' ', (string) ($intent['status'] ?? 'draft')))) ?>
                                    <?php if ((int) ($intent['is_late'] ?? 0) === 1): ?>
                                        <br><span class="muted">Late</span>
                                    <?php endif; ?>
                                </td>
                                <td><a class="button button-secondary button-small" href="<?= e(url_for('subscriptions/index', ['intent' => (int) $intent['id']])) ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
