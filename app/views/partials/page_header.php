<?php
/**
 * page_header.php
 *
 * Reusable page header with title, intro, and optional actions.
 * 
 * Required variables:
 *   - $title (string): Main page heading
 * 
 * Optional variables:
 *   - $eyebrow (string): Badge label above title (e.g., "Admin", "Bookings")
 *   - $intro (string): Subtitle/description text
 *   - $primaryAction (array): ['label' => 'Post a task', 'href' => url_for(...)]
 *   - $secondaryAction (array): ['label' => 'Cancel', 'href' => url_for(...)]
 *   - $secondaryLink (array): ['label' => 'Back', 'href' => url_for(...)]
 * 
 * Usage:
 *   <?php $pageHeader = [
 *       'title' => 'My Tasks',
 *       'eyebrow' => 'Client Workspace',
 *       'intro' => 'Track what is open, what is booked, and what is already completed...',
 *       'primaryAction' => ['label' => 'Post a Task', 'href' => url_for('tasks/create')],
 *   ];
 *   require BASE_PATH . '/app/views/partials/page_header.php'; ?>
 */
 
$title = $title ?? null;
$eyebrow = $eyebrow ?? null;
$intro = $intro ?? null;
$primaryAction = $primaryAction ?? null;
$secondaryAction = $secondaryAction ?? null;
$secondaryLink = $secondaryLink ?? null;
?>
<div class="page-head">
    <div>
        <?php if ($eyebrow): ?>
            <span class="eyebrow"><?= e($eyebrow) ?></span>
        <?php endif; ?>
        <?php if ($title): ?>
            <h1><?= e($title) ?></h1>
        <?php endif; ?>
        <?php if ($intro): ?>
            <p class="page-intro"><?= e($intro) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($primaryAction || $secondaryAction || $secondaryLink): ?>
        <div class="page-actions">
            <?php if ($secondaryLink): ?>
                <a class="button button-secondary button-small" href="<?= e($secondaryLink['href']) ?>">
                    <?= e($secondaryLink['label']) ?>
                </a>
            <?php endif; ?>
            <?php if ($primaryAction): ?>
                <a class="button" href="<?= e($primaryAction['href']) ?>">
                    <?= e($primaryAction['label']) ?>
                </a>
            <?php endif; ?>
            <?php if ($secondaryAction): ?>
                <a class="button button-secondary" href="<?= e($secondaryAction['href']) ?>">
                    <?= e($secondaryAction['label']) ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
