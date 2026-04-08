<div class="container">
    <section class="panel">
        <?php
        $title = 'Subscriptions and MoMo Transactions';
        $eyebrow = 'Admin';
        $intro = 'Review live subscriptions, inspect payment attempts, and resolve exceptions with audit notes.';
        $pagination = is_array($pagination ?? null) ? $pagination : ['total' => max(count($subscriptions), count($transactions)), 'page' => 1, 'total_pages' => 1];
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="section-head">
            <div>
                <h2>Current subscriptions</h2>
                <p class="section-intro">Latest known subscription record for each user.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>User</th><th>Plan</th><th>Status</th><th>Usage</th><th>Trial ends</th><th>Paid through</th><th>MoMo ref</th></tr>
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
                            <td><?= e((string) ($subscription['daily_applications_count'] ?? 0)) ?>/<?= e((string) ($subscription['max_applications_per_day'] ?? 0)) ?> today</td>
                            <td><?= !empty($subscription['trial_ends_at']) ? e(dateFmt((string) $subscription['trial_ends_at'])) : '—' ?></td>
                            <td><?= !empty($subscription['current_period_ends_at']) ? e(dateFmt((string) $subscription['current_period_ends_at'])) : '—' ?></td>
                            <td><span class="text-mono"><?= e((string) ($subscription['momo_reference'] ?? '')) ?></span></td>
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
                <p class="section-intro">Manual resolution requires a written audit note.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Created</th><th>User</th><th>Amount</th><th>Status</th><th>Reference</th><th>Resolve</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= e(dateFmt((string) $transaction['created_at'])) ?></td>
                            <td><strong><?= e((string) $transaction['full_name']) ?></strong><br><span class="text-mono"><?= e((string) $transaction['email']) ?></span></td>
                            <td><?= e(moneyRwf((int) $transaction['amount_rwf'])) ?></td>
                            <td><?= e((string) $transaction['status']) ?></td>
                            <td><span class="text-mono"><?= e((string) $transaction['external_ref']) ?></span></td>
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
                    <tr><th>Created</th><th>Decision</th><th>IP</th><th>Reference</th></tr>
                </thead>
                <tbody>
                    <?php foreach (($webhookLogs ?? []) as $log): ?>
                        <tr>
                            <td><?= e(dateFmt((string) $log['created_at'])) ?></td>
                            <td><?= e((string) $log['decision']) ?></td>
                            <td><span class="text-mono"><?= e((string) $log['request_ip']) ?></span></td>
                            <td><span class="text-mono"><?= e((string) ($log['external_ref'] ?? '')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
