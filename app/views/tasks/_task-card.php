<?php
// Render a single task card for use in lists (index, browse, etc)
// Variables expected: $task (associative array with title, category_name, city, country, status, budget, scheduled_for, id)
?>
<article class="task-card" style="position: relative;">
    <!-- Status badge: positioned top-right -->
    <div style="position: absolute; top: var(--space-4); right: var(--space-4);">
        <?php $status = (string) $task['status']; $label = ucfirst((string) $task['status']); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
    </div>

    <!-- Task info -->
    <div style="padding-right: var(--space-10);">
        <h3 style="margin: 0 0 var(--space-2) 0; font-size: var(--font-lg); line-height: 1.4;">
            <?= e((string) $task['title']) ?>
        </h3>
        <?php if (!empty($task['tasker_id'])): ?>
            <div style="margin-bottom: var(--space-3); color: var(--color-text-muted); font-size: var(--font-sm);">
                <?= $task['status'] === 'completed' ? 'Completed by' : 'Booked with' ?>
                <a href="<?= e(url_for('profile/view', ['id' => (int) $task['tasker_id']])) ?>">
                    <?= e((string) $task['tasker_name']) ?>
                </a>
            </div>
        <?php endif; ?>
        <div style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-4); color: var(--color-text-muted); font-size: var(--font-sm);">
            <span style="color: var(--color-primary); font-weight: 500;"><?= e((string) $task['category_name']) ?></span>
            <span>•</span>
            <span>📍 <?= e((string) $task['city']) ?>, <?= e((string) $task['country']) ?></span>
        </div>
    </div>

    <!-- Price & Schedule -->
    <div style="display: grid; grid-template-columns: auto 1fr; gap: var(--space-6); margin-bottom: var(--space-5); padding-bottom: var(--space-5); border-bottom: 1px solid var(--color-border);">
        <div>
            <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-1);">Budget</span>
            <strong style="font-size: 1.25rem; color: var(--color-primary-strong);">
                <?= e(moneyRwf($task['budget'])) ?>
            </strong>
        </div>
        <div>
            <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-1);">Schedule</span>
            <div style="color: var(--color-text);">
                <?php if (!empty($task['scheduled_for'])): ?>
                    <?= e(format_datetime((string) $task['scheduled_for'])) ?>
                <?php else: ?>
                    <span style="color: var(--color-text-muted);">No date scheduled</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
        <a class="button button-secondary button-small" href="<?= e(url_for('tasks/show', ['id' => (int) $task['id']])) ?>">
            View task
        </a>
        <?php if ($task['status'] === 'open'): ?>
            <a class="button button-secondary button-small" href="<?= e(url_for('tasks/edit', ['id' => (int) $task['id']])) ?>">
                Edit
            </a>
            <form method="post" action="<?= e(url_for('tasks/cancel')) ?>" style="display: inline;">
                <?= Csrf::input() ?>
                <input type="hidden" name="id" value="<?= e((string) $task['id']) ?>">
                <button type="submit" class="button button-danger button-small" data-confirm="Cancel this task? All bids will be withdrawn.">
                    Cancel
                </button>
            </form>
        <?php endif; ?>
    </div>
</article>
