<div class="container">
    <section class="panel">
        <?php
        $visibleTasks = array_values(array_filter($tasks, static fn (array $task): bool => (int) $task['is_active'] === 1));
        $hiddenTasks = array_values(array_filter($tasks, static fn (array $task): bool => (int) $task['is_active'] !== 1));
        $openTasks = array_values(array_filter($tasks, static fn (array $task): bool => (string) $task['status'] === 'open'));
        $pagination = is_array($pagination ?? null) ? $pagination : ['total' => count($tasks), 'page' => 1, 'total_pages' => 1];
        ?>
        <?php
        $title = 'Manage Tasks';
        $eyebrow = 'Admin';
        $intro = 'Review task activity and toggle visibility to keep the marketplace clean and safe.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="table-toolbar">
            <div>
                <h2>All Tasks</h2>
                <p class="muted">Spot risky listings quickly while keeping budgets and locations readable for moderation.</p>
            </div>
            <span class="pill pill-info"><?= e((string) ($pagination['total'] ?? count($tasks))) ?> task<?= (int) ($pagination['total'] ?? count($tasks)) !== 1 ? 's' : '' ?></span>
        </div>

        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Visible tasks</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($visibleTasks)) ?></strong>
                    <span>Listings that can currently appear in the marketplace</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Hidden tasks</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($hiddenTasks)) ?></strong>
                    <span>Listings removed from discovery but still retained in history</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Open lifecycle tasks</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($openTasks)) ?></strong>
                    <span>Tasks still awaiting acceptance or moderation review</span>
                </div>
            </article>
        </div>

        <?php if ($tasks === []): ?>
            <?php
            $emptyIcon = '📋';
            $emptyTitle = 'No tasks found';
            $emptyMessage = 'The marketplace has no tasks right now or the task list could not be loaded.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Client</th>
                            <th class="hide-mobile">Category</th>
                            <th>Status</th>
                            <th>Visibility</th>
                            <th>Budget</th>
                            <th class="hide-mobile">Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><strong><?= e((string) $task['title']) ?></strong></td>
                                <td><?= e((string) $task['client_name']) ?></td>
                                <td class="hide-mobile"><span class="entity-chip"><?= e((string) $task['category_name']) ?></span></td>
                                <td>
                                    <?php
                                    $status = (string) $task['status'];
                                    $label = ucfirst((string) $task['status']);
                                    require BASE_PATH . '/app/views/partials/status-badge.php';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status = (int) $task['is_active'] === 1 ? 'active' : 'inactive';
                                    $label = (int) $task['is_active'] === 1 ? 'Visible' : 'Hidden';
                                    require BASE_PATH . '/app/views/partials/status-badge.php';
                                    ?>
                                </td>
                                <td><strong><?= e(moneyRwf($task['budget'])) ?></strong></td>
                                <td class="hide-mobile"><?= e((string) $task['city']) ?>, <?= e((string) $task['country']) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <form method="post" action="<?= e(url_for('admin/toggle-task')) ?>">
                                            <?= Csrf::input() ?>
                                            <input type="hidden" name="task_id" value="<?= e((string) $task['id']) ?>">
                                            <button
                                                type="submit"
                                                class="button <?= (int) $task['is_active'] === 1 ? 'button-danger' : 'button-success' ?> button-small"
                                                data-confirm="Change this task visibility?"
                                            >
                                                <?= (int) $task['is_active'] === 1 ? 'Hide' : 'Show' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $paginationRoute = 'admin/tasks';
            $paginationParams = [];
            require BASE_PATH . '/app/views/partials/pagination.php';
            ?>
        <?php endif; ?>
    </section>
</div>
