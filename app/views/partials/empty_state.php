<?php
$emptyTitle = (string) ($emptyTitle ?? 'Nothing here yet');
$emptyMessage = (string) ($emptyMessage ?? '');
$emptyIcon = $emptyIcon ?? null;
$emptyAction = is_array($emptyAction ?? null) ? $emptyAction : null;
$emptyClass = trim('empty-state ' . (string) ($emptyClass ?? ''));
?>
<div class="<?= e($emptyClass) ?>">
    <?php if ($emptyIcon !== null && $emptyIcon !== ''): ?>
        <div class="empty-state-icon" aria-hidden="true"><?= e((string) $emptyIcon) ?></div>
    <?php endif; ?>

    <h3><?= e($emptyTitle) ?></h3>

    <?php if ($emptyMessage !== ''): ?>
        <p class="muted"><?= e($emptyMessage) ?></p>
    <?php endif; ?>

    <?php if ($emptyAction !== null && isset($emptyAction['href'], $emptyAction['label'])): ?>
        <div class="empty-state-actions">
            <a
                class="<?= e((string) ($emptyAction['class'] ?? 'button button-secondary')) ?>"
                href="<?= e((string) $emptyAction['href']) ?>"
            >
                <?= e((string) $emptyAction['label']) ?>
            </a>
        </div>
    <?php endif; ?>
</div>
