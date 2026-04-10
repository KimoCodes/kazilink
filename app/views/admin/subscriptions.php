<div class="container">
    <section class="panel">
        <?php
        $title = 'Subscriptions and MoMo Transactions';
        $eyebrow = 'Admin';
        $intro = 'Review live subscriptions, inspect payment attempts, and verify manual MTN MoMo screenshot payments before activation.';
        $pagination = is_array($pagination ?? null) ? $pagination : ['total' => max(count($subscriptions), count($transactions)), 'page' => 1, 'total_pages' => 1];
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        $pendingPaymentIntents = is_array($pendingPaymentIntents ?? null) ? $pendingPaymentIntents : [];
        $recentPaymentIntents = is_array($recentPaymentIntents ?? null) ? $recentPaymentIntents : [];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="section-head">
            <div>
                <h2>Pending manual payment proofs</h2>
                <p class="section-intro">Verify amount, date, phone clues, screenshot quality, and the 48-hour cutoff before approving.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>User</th><th>Plan</th><th>Amount</th><th>Activation</th><th class="hide-mobile">Deadline</th><th class="hide-mobile">Submitted</th><th>Proof</th><th>Review</th></tr>
                </thead>
                <tbody>
                    <?php if ($pendingPaymentIntents === []): ?>
                        <tr><td colspan="8">No payment proofs are waiting for review.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pendingPaymentIntents as $intent): ?>
                        <tr>
                            <td><strong><?= e((string) $intent['full_name']) ?></strong><br><span class="text-mono"><?= e((string) $intent['email']) ?></span></td>
                            <td><?= e((string) $intent['plan_name']) ?></td>
                            <td>
                                Expected: <?= e(moneyRwf((int) $intent['amount_expected_rwf'])) ?><br>
                                <span class="muted">Paid: <?= !empty($intent['amount_paid_rwf']) ? e(moneyRwf((int) $intent['amount_paid_rwf'])) : 'Not provided' ?></span>
                            </td>
                            <td>
                                <?= e(dateFmt((string) $intent['intended_activation_at'])) ?>
                                <?php if ((int) ($intent['is_late'] ?? 0) === 1): ?>
                                    <br><span class="muted">Late submission</span>
                                <?php endif; ?>
                            </td>
                            <td class="hide-mobile"><?= e(dateFmt((string) $intent['deadline_at'])) ?></td>
                            <td class="hide-mobile"><?= e(dateFmt((string) ($intent['submitted_at'] ?? ''))) ?></td>
                            <td>
                                <span class="text-mono"><?= e((string) ($intent['momo_number_displayed'] ?? '')) ?></span><br>
                                <span class="muted">Payer: <?= !empty($intent['payer_phone']) ? e((string) $intent['payer_phone']) : 'Not provided' ?></span><br>
                                <?php if (!empty($intent['screenshot_url'])): ?>
                                    <a class="button-link" href="<?= e(public_url((string) $intent['screenshot_url'])) ?>" target="_blank" rel="noopener">Open screenshot</a>
                                <?php else: ?>
                                    <span class="muted">No file</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= e(url_for('admin/approve-subscription-payment-intent')) ?>" class="stack-form" style="margin-bottom: var(--space-4);">
                                    <?= Csrf::input() ?>
                                    <input type="hidden" name="payment_intent_id" value="<?= e((string) $intent['id']) ?>">
                                    <button type="submit" class="button button-small" <?= (int) ($intent['is_late'] ?? 0) === 1 ? 'disabled' : '' ?>>Approve</button>
                                </form>
                                <form method="post" action="<?= e(url_for('admin/reject-subscription-payment-intent')) ?>" class="stack-form">
                                    <?= Csrf::input() ?>
                                    <input type="hidden" name="payment_intent_id" value="<?= e((string) $intent['id']) ?>">
                                    <textarea name="rejection_reason" rows="3" placeholder="Required rejection reason"></textarea>
                                    <button type="submit" class="button button-secondary button-small">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($recentPaymentIntents !== []): ?>
            <div class="section-head">
                <div>
                    <h2>Recent manual payment activity</h2>
                    <p class="section-intro">Use this list to confirm what was approved, rejected, activated, or expired most recently.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr><th>Updated</th><th>User</th><th>Plan</th><th>Status</th><th class="hide-mobile">Submitted</th><th class="hide-mobile">Reviewed</th><th>Reason</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPaymentIntents as $intent): ?>
                            <tr>
                                <td><?= e(dateFmt((string) $intent['updated_at'])) ?></td>
                                <td><strong><?= e((string) $intent['full_name']) ?></strong><br><span class="text-mono"><?= e((string) $intent['email']) ?></span></td>
                                <td><?= e((string) $intent['plan_name']) ?></td>
                                <td>
                                    <?= e((string) $intent['status']) ?>
                                    <?php if ((int) ($intent['is_late'] ?? 0) === 1): ?>
                                        <br><span class="muted">Late</span>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile"><?= !empty($intent['submitted_at']) ? e(dateFmt((string) $intent['submitted_at'])) : '—' ?></td>
                                <td class="hide-mobile"><?= !empty($intent['reviewed_at']) ? e(dateFmt((string) $intent['reviewed_at'])) : '—' ?></td>
                                <td class="cell-wrap"><?= !empty($intent['rejection_reason']) ? e((string) $intent['rejection_reason']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="section-head">
            <div>
                <h2>Current subscriptions</h2>
                <p class="section-intro">Latest known subscription record for each user.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>User</th><th>Plan</th><th>Status</th><th class="hide-mobile">Usage</th><th class="hide-mobile">Trial ends</th><th class="hide-mobile">Paid through</th><th class="hide-mobile">MoMo ref</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <tr>
                            <td><strong><?= e((string) $subscription['full_name']) ?></strong><br><span class="text-mono"><?= e((string) $subscription['email']) ?></span></td>
                            <td>
                                <?= e((string) $subscription['plan_name']) ?> (P<?= e((string) $subscription['priority_level']) ?>)
                                <?php if (!empty($subscription['badge_name'])): ?>
                                    <br><span class="subscription-tier-badge subscription-tier-badge-inline"><?= e((string) $subscription['badge_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) $subscription['status']) ?></td>
                            <td class="hide-mobile"><?= e((string) ($subscription['daily_applications_count'] ?? 0)) ?>/<?= e((string) ($subscription['max_applications_per_day'] ?? 0)) ?> today</td>
                            <td class="hide-mobile"><?= !empty($subscription['trial_ends_at']) ? e(dateFmt((string) $subscription['trial_ends_at'])) : '—' ?></td>
                            <td class="hide-mobile"><?= !empty($subscription['current_period_ends_at']) ? e(dateFmt((string) $subscription['current_period_ends_at'])) : '—' ?></td>
                            <td class="hide-mobile"><span class="text-mono"><?= e((string) ($subscription['momo_reference'] ?? '')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $paginationRoute = 'admin/subscriptions';
        $paginationParams = [];
        require BASE_PATH . '/app/views/partials/pagination.php';
        ?>

        <div class="section-head">
            <div>
                <h2>MoMo transactions</h2>
                <p class="section-intro">Legacy request-to-pay transactions can still be manually resolved with a written audit note.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Created</th><th>User</th><th>Amount</th><th>Status</th><th class="hide-mobile">Reference</th><th>Resolve</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= e(dateFmt((string) $transaction['created_at'])) ?></td>
                            <td><strong><?= e((string) $transaction['full_name']) ?></strong><br><span class="text-mono"><?= e((string) $transaction['email']) ?></span></td>
                            <td><?= e(moneyRwf((int) $transaction['amount_rwf'])) ?></td>
                            <td><?= e((string) $transaction['status']) ?></td>
                            <td class="hide-mobile"><span class="text-mono"><?= e((string) $transaction['external_ref']) ?></span></td>
                            <td>
                                <form method="post" action="<?= e(url_for('admin/resolve-momo-transaction')) ?>" class="stack-form">
                                    <?= Csrf::input() ?>
                                    <input type="hidden" name="transaction_id" value="<?= e((string) $transaction['id']) ?>">
                                    <select name="status">
                                        <option value="successful">Mark successful</option>
                                        <option value="failed">Mark failed</option>
                                    </select>
                                    <textarea name="notes" rows="2" placeholder="Required audit note"></textarea>
                                    <button type="submit" class="button button-secondary button-small">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section-head">
            <div>
                <h2>Webhook logs</h2>
                <p class="section-intro">Use these entries to confirm callback delivery and IP allowlist behavior in production.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Created</th><th>Decision</th><th class="hide-mobile">IP</th><th class="hide-mobile">Reference</th></tr>
                </thead>
                <tbody>
                    <?php foreach (($webhookLogs ?? []) as $log): ?>
                        <tr>
                            <td><?= e(dateFmt((string) $log['created_at'])) ?></td>
                            <td><?= e((string) $log['decision']) ?></td>
                            <td class="hide-mobile"><span class="text-mono"><?= e((string) $log['request_ip']) ?></span></td>
                            <td class="hide-mobile"><span class="text-mono"><?= e((string) ($log['external_ref'] ?? '')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
