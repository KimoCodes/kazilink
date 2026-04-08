<?php
$status = (string) $dispute['status'];
$label = ucfirst(str_replace('_', ' ', $status));
?>
<div class="container">
    <section class="panel">
        <?php
        $title = dispute_type_label((string) $dispute['type']);
        $eyebrow = 'Issue Report';
        $intro = 'This record ties the reported issue back to the hiring agreement that created the job relationship on the platform.';
        $primaryAction = ['label' => 'Open agreement', 'href' => url_for('agreements/review', ['id' => (int) $dispute['agreement_id']])];
        $secondaryAction = ['label' => 'Download PDF', 'href' => url_for('disputes/download', ['id' => (int) $dispute['id']])];
        $secondaryLink = ['label' => Auth::role() === 'admin' ? 'Back to disputes' : 'Back to bookings', 'href' => Auth::role() === 'admin' ? url_for('admin/disputes') : url_for('bookings/index')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="detail-facts">
            <div class="detail-fact">
                <span class="detail-fact-label">Issue type</span>
                <div class="detail-fact-value"><?= e(dispute_type_label((string) $dispute['type'])) ?></div>
            </div>
            <div class="detail-fact">
                <span class="detail-fact-label">Status</span>
                <div class="detail-fact-value"><?php require BASE_PATH . '/app/views/partials/status-badge.php'; ?></div>
            </div>
            <div class="detail-fact">
                <span class="detail-fact-label">Agreement UID</span>
                <div class="detail-fact-value"><code class="entity-chip text-mono"><?= e((string) $dispute['agreement_uid']) ?></code></div>
            </div>
            <div class="detail-fact">
                <span class="detail-fact-label">Reported</span>
                <div class="detail-fact-value"><?= e(dateFmt((string) $dispute['created_at'])) ?></div>
            </div>
        </div>

        <div class="detail-copy">
            <p><strong>Job:</strong> <?= e((string) $dispute['job_title']) ?></p>
            <p><strong>Reporter:</strong> <?= e((string) ($dispute['reporter_name'] ?: $dispute['reporter_email'])) ?></p>
            <p><strong>Description:</strong><br><?= nl2br(e((string) $dispute['description'])) ?></p>
            <p class="muted">Download opens a print-ready issue record. Use your browser's Print to PDF option to save a copy.</p>
            <?php if (!empty($dispute['admin_notes'])): ?>
                <p><strong>Admin note:</strong><br><?= nl2br(e((string) $dispute['admin_notes'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($dispute['resolved_at'])): ?>
                <p><strong>Closed at:</strong> <?= e(dateFmt((string) $dispute['resolved_at'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($dispute['admin_updated_by_name'])): ?>
                <p><strong>Last updated by:</strong> <?= e((string) $dispute['admin_updated_by_name']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (Auth::role() === 'admin'): ?>
            <section class="action-panel">
                <div>
                    <strong>Update dispute status</strong>
                    <p class="muted">Move the report through review, resolution, or rejection as you investigate the case.</p>
                </div>
                <form method="post" action="<?= e(url_for('admin/update-dispute-status')) ?>" class="table-actions">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="dispute_id" value="<?= e((string) $dispute['id']) ?>">
                    <select name="status" aria-label="Dispute status">
                        <?php foreach (['open', 'under_review', 'resolved', 'rejected'] as $statusOption): ?>
                            <option value="<?= e($statusOption) ?>" <?= (string) $dispute['status'] === $statusOption ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $statusOption))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="admin_notes" value="<?= e((string) ($dispute['admin_notes'] ?? '')) ?>" placeholder="Add admin resolution note" maxlength="4000">
                    <button type="submit" class="button button-secondary">Update status</button>
                </form>
            </section>
        <?php endif; ?>
    </section>
</div>
