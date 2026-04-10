<div class="container">
    <section class="panel">
        <?php
        $openDisputes = array_values(array_filter($disputes, static fn (array $dispute): bool => (string) $dispute['status'] === 'open'));
        $underReviewDisputes = array_values(array_filter($disputes, static fn (array $dispute): bool => (string) $dispute['status'] === 'under_review'));
        $resolvedDisputes = array_values(array_filter($disputes, static fn (array $dispute): bool => in_array((string) $dispute['status'], ['resolved', 'rejected'], true)));
        $title = 'Disputes';
        $eyebrow = 'Admin';
        $intro = 'Review reported hiring issues and jump directly into the linked dispute record and agreement.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Open</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($openDisputes)) ?></strong>
                    <span>Reports waiting for admin attention</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Under review</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($underReviewDisputes)) ?></strong>
                    <span>Issues being investigated</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Closed</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($resolvedDisputes)) ?></strong>
                    <span>Resolved or rejected reports</span>
                </div>
            </article>
        </div>

        <?php if ($disputes === []): ?>
            <?php
            $emptyIcon = '⚖️';
            $emptyTitle = 'No disputes yet';
            $emptyMessage = 'Reported issues will appear here once a client or tasker submits one.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Type</th>
                            <th>Agreement</th>
                            <th class="hide-mobile">Reporter</th>
                            <th class="hide-mobile">Parties</th>
                            <th>Status</th>
                            <th class="hide-mobile">Admin note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($disputes as $dispute): ?>
                            <tr>
                                <td><?= e(dateFmt((string) $dispute['created_at'])) ?></td>
                                <td><?= e(dispute_type_label((string) $dispute['type'])) ?></td>
                                <td>
                                    <code class="entity-chip text-mono"><?= e((string) $dispute['agreement_uid']) ?></code><br>
                                    <span class="text-muted"><?= e((string) $dispute['job_title']) ?></span>
                                </td>
                                <td class="hide-mobile"><?= e((string) ($dispute['reporter_name'] ?: 'Unknown reporter')) ?></td>
                                <td class="hide-mobile"><?= e((string) ($dispute['client_name'] ?: 'Client')) ?> / <?= e((string) ($dispute['tasker_name'] ?: 'Tasker')) ?></td>
                                <td>
                                    <?php $status = (string) $dispute['status']; $label = ucfirst(str_replace('_', ' ', $status)); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                                </td>
                                <td class="hide-mobile cell-wrap">
                                    <?= !empty($dispute['admin_notes']) ? e(mb_strimwidth((string) $dispute['admin_notes'], 0, 80, '...')) : '<span class="text-muted">No admin note</span>' ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <form method="post" action="<?= e(url_for('admin/update-dispute-status')) ?>">
                                            <?= Csrf::input() ?>
                                            <input type="hidden" name="dispute_id" value="<?= e((string) $dispute['id']) ?>">
                                            <select name="status" aria-label="Update dispute status">
                                                <?php foreach (['open', 'under_review', 'resolved', 'rejected'] as $statusOption): ?>
                                                    <option value="<?= e($statusOption) ?>" <?= (string) $dispute['status'] === $statusOption ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $statusOption))) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="admin_notes" value="<?= e((string) ($dispute['admin_notes'] ?? '')) ?>" placeholder="Admin note" maxlength="4000">
                                            <button type="submit" class="button button-secondary button-small">Save</button>
                                        </form>
                                        <a class="button button-secondary button-small" href="<?= e(url_for('disputes/show', ['id' => (int) $dispute['id']])) ?>">Open dispute</a>
                                        <a class="button button-secondary button-small" href="<?= e(url_for('agreements/review', ['id' => (int) $dispute['agreement_id']])) ?>">Open agreement</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
