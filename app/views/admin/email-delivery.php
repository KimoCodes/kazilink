<div class="container">
    <section class="panel">
        <?php
        $title = 'Email Delivery';
        $eyebrow = 'Admin';
        $intro = 'Inspect queued, sent, retried, failed, and skipped email deliveries across payments, contact replies, and system notices.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        $metrics = is_array($metrics ?? null) ? $metrics : [];
        $filters = is_array($filters ?? null) ? $filters : [];
        $emails = is_array($emails ?? null) ? $emails : [];
        ?>

        <div class="section-head">
            <div>
                <h2>Queue summary</h2>
                <p class="section-intro">Use these counts to spot backlog growth, retries, and permanent delivery failures quickly.</p>
            </div>
        </div>

        <div class="stats-grid">
            <?php foreach ([
                'pending' => 'Pending',
                'retry_scheduled' => 'Retry scheduled',
                'sent' => 'Sent',
                'failed_permanent' => 'Failed permanent',
                'skipped' => 'Skipped',
            ] as $statusKey => $label): ?>
                <article class="stat-card">
                    <span class="stat-label"><?= e($label) ?></span>
                    <strong class="stat-value"><?= e((string) ($metrics[$statusKey] ?? 0)) ?></strong>
                </article>
            <?php endforeach; ?>
        </div>

        <form method="get" action="<?= e(url_for('admin/email-delivery')) ?>" class="stack-form" style="margin-top: var(--space-6);">
            <input type="hidden" name="route" value="admin/email-delivery">
            <div class="form-grid">
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        <?php foreach (['pending', 'processing', 'retry_scheduled', 'sent', 'failed_transient', 'failed_permanent', 'skipped'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Event name</span>
                    <input type="text" name="event_name" value="<?= e((string) ($filters['event_name'] ?? '')) ?>" placeholder="payment_submitted">
                </label>
                <label>
                    <span>Recipient email</span>
                    <input type="text" name="recipient_email" value="<?= e((string) ($filters['recipient_email'] ?? '')) ?>" placeholder="user@example.com">
                </label>
            </div>
            <div>
                <button type="submit" class="button button-secondary">Apply filters</button>
            </div>
        </form>

        <div class="section-head">
            <div>
                <h2>Recent deliveries</h2>
                <p class="section-intro">Each row is one deduplicated email outbox record. Use resend only for failed or skipped rows.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Created</th><th>Recipient</th><th>Event</th><th>Status</th><th class="hide-mobile">Attempts</th><th>Error</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if ($emails === []): ?>
                        <tr><td colspan="7">No email delivery records matched the current filters.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($emails as $email): ?>
                        <?php $latestAttempt = is_array($email['latest_attempt'] ?? null) ? $email['latest_attempt'] : null; ?>
                        <tr>
                            <td><?= e(dateFmt((string) $email['created_at'])) ?></td>
                            <td><strong><?= e((string) ($email['recipient_name'] ?: 'Recipient')) ?></strong><br><span class="text-mono"><?= e((string) $email['recipient_email']) ?></span></td>
                            <td><strong><?= e((string) $email['event_name']) ?></strong><br><span class="muted"><?= e((string) $email['template_name']) ?></span></td>
                            <td><?= e((string) $email['status']) ?><?php if (!empty($email['sent_at'])): ?><br><span class="muted"><?= e(dateFmt((string) $email['sent_at'])) ?></span><?php endif; ?></td>
                            <td class="hide-mobile"><?= e((string) $email['attempt_count']) ?><?php if ($latestAttempt !== null): ?><br><span class="muted">Last: <?= e((string) $latestAttempt['result']) ?></span><?php endif; ?></td>
                            <td class="cell-wrap"><?= !empty($email['last_error_message']) ? e((string) $email['last_error_message']) : '—' ?></td>
                            <td>
                                <?php if (in_array((string) $email['status'], ['failed_transient', 'failed_permanent', 'skipped'], true)): ?>
                                    <form method="post" action="<?= e(url_for('admin/resend-email-delivery')) ?>" class="stack-form">
                                        <?= Csrf::input() ?>
                                        <input type="hidden" name="outbox_id" value="<?= e((string) $email['id']) ?>">
                                        <button type="submit" class="button button-small">Queue resend</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
