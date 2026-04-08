<?php
$agreementForTask = $agreementsByBooking[(int) ($task['booking_id'] ?? 0)] ?? null;
$agreementStatus = is_array($agreementForTask) ? (string) ($agreementForTask['status'] ?? '') : '';
$agreedAmount = (float) ($task['agreed_amount'] ?? $task['budget'] ?? 0);
?>
<article class="task-card task-card-client">
    <div class="task-card-header">
        <div class="task-card-title-wrap">
            <h3 class="task-card-title task-card-title-compact"><?= e((string) $task['title']) ?></h3>
            <?php if (!empty($task['tasker_id'])): ?>
                <div class="task-card-assignee">
                    <?= $task['status'] === 'completed' ? 'Completed by' : 'Booked with' ?>
                    <a href="<?= e(url_for('profile/view', ['id' => (int) $task['tasker_id']])) ?>">
                        <?= e((string) $task['tasker_name']) ?>
                    </a>
                </div>
            <?php endif; ?>
            <div class="task-card-meta">
                <span class="task-card-category"><?= e((string) $task['category_name']) ?></span>
                <span>•</span>
                <span>📍 <?= e((string) $task['city']) ?>, <?= e((string) $task['country']) ?></span>
            </div>
        </div>
        <div class="button-group task-card-statuses">
            <?php $status = (string) $task['status']; $label = ucfirst((string) $task['status']); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
            <?php if ($agreementStatus !== ''): ?>
                <span class="pill <?= $agreementStatus === 'accepted' ? 'pill-success' : ($agreementStatus === 'disputed' ? 'pill-warning' : 'pill-info') ?>"><?= e(agreement_status_label($agreementStatus)) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="task-card-metrics">
        <div>
            <span class="task-card-metric-label"><?= $task['status'] === 'completed' ? 'Agreed amount' : 'Budget' ?></span>
            <strong class="task-card-metric-value"><?= e(moneyRwf($agreedAmount)) ?></strong>
        </div>
        <div>
            <span class="task-card-metric-label">Schedule</span>
            <div class="task-card-metric-copy">
                <?php if (!empty($task['scheduled_for'])): ?>
                    <?= e(format_datetime((string) $task['scheduled_for'])) ?>
                <?php else: ?>
                    <span class="text-muted">No date scheduled</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="task-card-footer">
        <div class="task-card-footer-copy">
            <?php if ($agreementStatus !== ''): ?>
                <span class="muted">
                    <?= $agreementStatus === 'accepted' ? 'This hire has a fully accepted agreement on record.' : 'Review the agreement record before work continues or issues are reported.' ?>
                </span>
            <?php else: ?>
                <span class="muted">Manage this task from its detail page.</span>
            <?php endif; ?>
        </div>
        <div class="button-group">
            <?php if (is_array($agreementForTask)): ?>
                <a class="button button-small" href="<?= e(url_for('agreements/review', ['id' => (int) $agreementForTask['id']])) ?>">
                    Review Agreement
                </a>
            <?php endif; ?>
            <a class="button button-secondary button-small" href="<?= e(url_for('tasks/show', ['id' => (int) $task['id']])) ?>">
                View task
            </a>
            <?php if ($task['status'] === 'open'): ?>
                <a class="button button-secondary button-small" href="<?= e(url_for('tasks/edit', ['id' => (int) $task['id']])) ?>">
                    Edit
                </a>
                <form method="post" action="<?= e(url_for('tasks/cancel')) ?>">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="id" value="<?= e((string) $task['id']) ?>">
                    <button type="submit" class="button button-danger button-small" data-confirm="Cancel this task? All bids will be withdrawn.">
                        Cancel
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>
