<div class="container">
    <section class="panel">
        <?php
        $title = 'Admin Dashboard';
        $eyebrow = 'Admin';
        $intro = 'Monitor marketplace activity, manage users, and moderate content when needed.';
        $primaryAction = ['label' => 'Manage users', 'href' => url_for('admin/users')];
        $secondaryAction = ['label' => 'View payments', 'href' => url_for('admin/payments')];
        unset($secondaryLink);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="admin-kpi-grid">
            <article class="admin-kpi-card admin-kpi-card-primary">
                <span class="admin-kpi-label">Total Users</span>
                <strong><?= e((string) $stats['total_users']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-success">
                <span class="admin-kpi-label">Active Clients</span>
                <strong><?= e((string) $stats['active_clients']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-info">
                <span class="admin-kpi-label">Active Taskers</span>
                <strong><?= e((string) $stats['active_taskers']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-violet">
                <span class="admin-kpi-label">Open Tasks</span>
                <strong><?= e((string) $stats['open_tasks']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-warning">
                <span class="admin-kpi-label">Booked Tasks</span>
                <strong><?= e((string) $stats['booked_tasks']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-success">
                <span class="admin-kpi-label">Completed Tasks</span>
                <strong><?= e((string) $stats['completed_tasks']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-primary">
                <span class="admin-kpi-label">Paid Payments</span>
                <strong><?= e((string) $stats['paid_payments']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-info">
                <span class="admin-kpi-label">Payment Volume</span>
                <strong><?= e(moneyRwf($stats['payments_volume'])) ?></strong>
            </article>
        </div>

        <div class="home-standards-grid admin-action-grid">
            <article class="feature-card">
                <h3>Moderate accounts</h3>
                <p class="muted">Deactivate access safely when an account should no longer participate in the marketplace.</p>
                <a class="button-link" href="<?= e(url_for('admin/users')) ?>">Open user moderation</a>
            </article>
            <article class="feature-card">
                <h3>Review task visibility</h3>
                <p class="muted">Hide risky or low-quality listings without corrupting the underlying task lifecycle status.</p>
                <a class="button-link" href="<?= e(url_for('admin/tasks')) ?>">Open task moderation</a>
            </article>
            <article class="feature-card">
                <h3>Audit recent actions</h3>
                <p class="muted">Use the activity log below to explain who changed what and when during demos or QA reviews.</p>
                <span class="pill pill-primary"><?= count($auditLogs) ?> recent audit entries</span>
            </article>
            <article class="feature-card">
                <h3>Review payments</h3>
                <p class="muted">Track pending, paid, failed, and expired Stripe Checkout sessions in one admin-friendly view.</p>
                <a class="button-link" href="<?= e(url_for('admin/payments')) ?>">Open payment records</a>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <h2>Recent Payments</h2>
                <p class="section-intro">Latest locally recorded Checkout sessions and webhook outcomes.</p>
            </div>
            <div class="page-actions">
                <a class="button button-secondary button-small" href="<?= e(url_for('admin/payments')) ?>">View all payments</a>
            </div>
        </div>

        <?php if ($recentPayments === []): ?>
            <?php
            $emptyIcon = '💳';
            $emptyTitle = 'No payments recorded yet';
            $emptyMessage = 'Checkout sessions and webhook-confirmed payments will appear here once Stripe is configured and used.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Plan</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPayments as $payment): ?>
                            <tr>
                                <td><?= e(dateFmt((string) ($payment['paid_at'] ?? $payment['created_at']))) ?></td>
                                <td>
                                    <strong><?= e((string) $payment['plan_name']) ?></strong><br>
                                    <span class="text-muted text-mono"><?= e((string) $payment['checkout_session_id']) ?></span>
                                </td>
                                <td><?= e((string) ($payment['customer_email'] ?: $payment['full_name'] ?: 'Guest checkout')) ?></td>
                                <td><?= e(moneyRwf((int) $payment['amount_minor'])) ?></td>
                                <td>
                                    <?php
                                    $status = (string) $payment['status'];
                                    $label = ucfirst((string) $payment['status']);
                                    require BASE_PATH . '/app/views/partials/status-badge.php';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <h2>Recent Admin Actions</h2>
                <p class="section-intro">Latest moderation events and account visibility changes.</p>
            </div>
        </div>

        <?php if ($auditLogs === []): ?>
            <?php
            $emptyIcon = '✓';
            $emptyTitle = 'No admin actions yet';
            $emptyMessage = 'All systems are calm and there have been no moderation events yet.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Admin</th>
                            <th>Target</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td><?= e(dateFmt((string) $log['created_at'])) ?></td>
                                <td><strong><?= e((string) $log['admin_name']) ?></strong></td>
                                <td><span class="entity-chip"><?= e(ucfirst((string) $log['target_type'])) ?> #<?= e((string) $log['target_id']) ?></span></td>
                                <td>
                                    <?php
                                    $status = (string) $log['action'];
                                    $label = ucfirst(str_replace('-', ' ', (string) $log['action']));
                                    require BASE_PATH . '/app/views/partials/status-badge.php';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
