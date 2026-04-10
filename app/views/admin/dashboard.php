<div class="container">
    <?php $ads = is_array($ads ?? null) ? $ads : []; ?>
    <section class="panel">
        <?php
        $title = 'Admin Dashboard';
        $eyebrow = 'Admin';
        $intro = 'Monitor marketplace activity, manage users, and moderate content when needed.';
        $primaryAction = ['label' => 'Review agreements', 'href' => url_for('admin/agreements')];
        $secondaryAction = ['label' => 'Review disputes', 'href' => url_for('admin/disputes')];
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
                <span class="admin-kpi-label">Pending Agreements</span>
                <strong><?= e((string) $stats['pending_agreements']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-info">
                <span class="admin-kpi-label">Accepted Agreements</span>
                <strong><?= e((string) $stats['accepted_agreements']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-warning">
                <span class="admin-kpi-label">Open Disputes</span>
                <strong><?= e((string) $stats['open_disputes']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-info">
                <span class="admin-kpi-label">Active Plans</span>
                <strong><?= e((string) $stats['active_plans']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-primary">
                <span class="admin-kpi-label">Live Subscriptions</span>
                <strong><?= e((string) $stats['subscriptions_live']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-warning">
                <span class="admin-kpi-label">Webhook Events</span>
                <strong><?= e((string) $stats['webhook_events']) ?></strong>
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
                <h3>Monitor agreement health</h3>
                <p class="muted">Use agreement and dispute metrics to spot where hires are not being fully accepted or where issues are trending.</p>
                <a class="button-link" href="<?= e(url_for('admin/agreements')) ?>">Open agreement queue</a>
            </article>
            <article class="feature-card">
                <h3>Publish ads</h3>
                <p class="muted">Create and manage promotional ads that appear on the home page and marketplace discovery screen.</p>
                <a class="button-link" href="<?= e(url_for('admin/ads')) ?>">Open ad manager</a>
            </article>
            <article class="feature-card">
                <h3>Manage plans</h3>
                <p class="muted">Edit monthly prices, control plan visibility levels, and disable tiers without changing code.</p>
                <a class="button-link" href="<?= e(url_for('admin/plans')) ?>">Open plan manager</a>
            </article>
            <article class="feature-card">
                <h3>Issue promo codes</h3>
                <p class="muted">Create global or user-targeted discounts with expiry dates and redemption limits.</p>
                <a class="button-link" href="<?= e(url_for('admin/promos')) ?>">Open promo manager</a>
            </article>
            <article class="feature-card">
                <h3>Subscription settings</h3>
                <p class="muted">Tune grace periods from the database so billing policy changes do not require a deploy.</p>
                <a class="button-link" href="<?= e(url_for('admin/settings')) ?>">Open settings</a>
            </article>
            <article class="feature-card">
                <h3>Theme and backgrounds</h3>
                <p class="muted">Upload page backgrounds, switch light or dark mode, and update brand colors from a single admin screen.</p>
                <a class="button-link" href="<?= e(url_for('admin/theme')) ?>">Open theme manager</a>
            </article>
            <article class="feature-card">
                <h3>Review subscription payments</h3>
                <p class="muted">Inspect MTN MoMo transactions, confirm edge cases, and keep every manual intervention auditable.</p>
                <a class="button-link" href="<?= e(url_for('admin/subscriptions')) ?>">Open subscriptions</a>
            </article>
            <article class="feature-card">
                <h3>Read contact messages</h3>
                <p class="muted">Open locally captured support requests and reply directly from the admin workspace.</p>
                <a class="button-link" href="<?= e(url_for('admin/messages')) ?>">Open contact inbox</a>
            </article>
            <article class="feature-card">
                <h3>Review newsletter signups</h3>
                <p class="muted">See who subscribed, which audience they chose, and where the signup came from without opening local files.</p>
                <a class="button-link" href="<?= e(url_for('admin/newsletter')) ?>">Open subscriber list</a>
            </article>
            <article class="feature-card">
                <h3>Inspect email delivery</h3>
                <p class="muted">Track queued, retried, failed, and sent emails from one place and safely resend only the affected items.</p>
                <a class="button-link" href="<?= e(url_for('admin/email-delivery')) ?>">Open email delivery</a>
            </article>
            <article class="feature-card">
                <h3>Review open issues</h3>
                <p class="muted">Investigate non-payment, no-show, and scope-change reports with the linked agreement record close at hand.</p>
                <a class="button-link" href="<?= e(url_for('admin/disputes')) ?>">Open dispute queue</a>
            </article>
        </div>
    </section>

    <?php
    if ($ads !== []) {
        require BASE_PATH . '/app/views/partials/ad-banner.php';
    }
    ?>

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
                            <th class="hide-mobile">Notes</th>
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
                                <td class="hide-mobile cell-wrap"><?= !empty($log['notes']) ? e((string) $log['notes']) : '<span class="text-muted">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
