<div class="container">
    <section class="panel">
        <?php
        $pendingAgreements = array_values(array_filter($agreements, static fn (array $agreement): bool => (string) $agreement['status'] === 'pending_acceptance'));
        $acceptedAgreements = array_values(array_filter($agreements, static fn (array $agreement): bool => (string) $agreement['status'] === 'accepted'));
        $disputedAgreements = array_values(array_filter($agreements, static fn (array $agreement): bool => (string) $agreement['status'] === 'disputed'));
        $title = 'Hiring Agreements';
        $eyebrow = 'Admin';
        $intro = 'Monitor hiring records, dual acceptance progress, and disputed agreements from one queue.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Pending acceptance</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($pendingAgreements)) ?></strong>
                    <span>Agreements still waiting on one or both parties</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Accepted</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($acceptedAgreements)) ?></strong>
                    <span>Fully executed hiring records</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Disputed</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($disputedAgreements)) ?></strong>
                    <span>Agreements with an attached issue report</span>
                </div>
            </article>
        </div>

        <?php if ($agreements === []): ?>
            <?php
            $emptyIcon = '📄';
            $emptyTitle = 'No agreements yet';
            $emptyMessage = 'Hiring agreements will appear here after a client accepts a bid and creates a booking.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>UID</th>
                            <th>Job</th>
                            <th>Client</th>
                            <th>Tasker</th>
                            <th>Status</th>
                            <th class="hide-mobile">Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agreements as $agreement): ?>
                            <tr>
                                <td><code class="entity-chip text-mono"><?= e((string) $agreement['agreement_uid']) ?></code></td>
                                <td>
                                    <strong><?= e((string) $agreement['job_title']) ?></strong><br>
                                    <span class="text-muted"><?= e((string) $agreement['category']) ?></span>
                                </td>
                                <td><?= e((string) ($agreement['client_name'] ?: 'Unknown client')) ?></td>
                                <td><?= e((string) ($agreement['tasker_name'] ?: 'Unknown tasker')) ?></td>
                                <td>
                                    <?php $status = (string) $agreement['status']; $label = agreement_status_label($status); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                                </td>
                                <td class="hide-mobile"><?= e(dateFmt((string) $agreement['created_at'])) ?></td>
                                <td><a class="button button-secondary button-small" href="<?= e(url_for('agreements/review', ['id' => (int) $agreement['id']])) ?>">Open agreement</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
