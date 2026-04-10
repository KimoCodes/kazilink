<?php
$pagination = is_array($pagination ?? null) ? $pagination : null;
$paginationRoute = (string) ($paginationRoute ?? '');
$paginationParams = is_array($paginationParams ?? null) ? $paginationParams : [];
if ($pagination === null || (int) ($pagination['total_pages'] ?? 1) <= 1 || $paginationRoute === '') {
    return;
}
?>
<nav class="pagination-nav" aria-label="Pagination">
    <div class="button-group">
        <?php if (!empty($pagination['has_previous'])): ?>
            <a class="button button-secondary button-small" href="<?= e(url_for($paginationRoute, array_merge($paginationParams, ['page' => (int) $pagination['previous_page']]))) ?>">Previous</a>
        <?php endif; ?>
        <span class="pill">Page <?= e((string) $pagination['page']) ?> of <?= e((string) $pagination['total_pages']) ?></span>
        <?php if (!empty($pagination['has_next'])): ?>
            <a class="button button-secondary button-small" href="<?= e(url_for($paginationRoute, array_merge($paginationParams, ['page' => (int) $pagination['next_page']]))) ?>">Next</a>
        <?php endif; ?>
    </div>
</nav>
