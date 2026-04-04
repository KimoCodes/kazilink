<div class="container">
    <section class="panel">
        <?php
        // Prepare variables for page_header partial
        $title = 'My tasks';
        $eyebrow = 'Client Workspace';
        $intro = 'Track what is open, what is booked, and what is already completed from one clean dashboard.';
        $primaryAction = [
            'label' => '+ Post a task',
            'href' => url_for('tasks/create')
        ];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <?php if ($tasks === []): ?>
            <?php
            $emptyIcon = '📝';
            $emptyTitle = 'No tasks posted yet';
            $emptyMessage = 'Start collecting bids from talented taskers.';
            $emptyAction = ['label' => 'Post your first task', 'href' => url_for('tasks/create'), 'class' => 'button'];
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <?php
            // Group tasks by status
            $openTasks = array_filter($tasks, fn($t) => $t['status'] === 'open');
            $bookedTasks = array_filter($tasks, fn($t) => $t['status'] === 'booked');
            $completedTasks = array_filter($tasks, fn($t) => $t['status'] === 'completed');
            ?>

            <!-- Open Tasks Section -->
            <?php if (!empty($openTasks)): ?>
                <div style="margin-bottom: var(--space-8);">
                    <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); padding-bottom: var(--space-4); border-bottom: 2px solid var(--color-border);">
                        <h2 style="margin: 0; color: var(--color-text);">🔔 Open</h2>
                        <span class="pill" style="background: var(--color-info-soft); color: var(--color-info);"><?= count($openTasks) ?> task<?= count($openTasks) !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="card-list">
                        <?php foreach ($openTasks as $task): ?>
                            <?php require BASE_PATH . '/app/views/tasks/_task-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Booked Tasks Section -->
            <?php if (!empty($bookedTasks)): ?>
                <div style="margin-bottom: var(--space-8);">
                    <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); padding-bottom: var(--space-4); border-bottom: 2px solid var(--color-border);">
                        <h2 style="margin: 0; color: var(--color-text);">✓ Booked</h2>
                        <span class="pill" style="background: var(--color-success-soft); color: var(--color-success);"><?= count($bookedTasks) ?> booking<?= count($bookedTasks) !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="card-list">
                        <?php foreach ($bookedTasks as $task): ?>
                            <?php require BASE_PATH . '/app/views/tasks/_task-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Completed Tasks Section -->
            <?php if (!empty($completedTasks)): ?>
                <div>
                    <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); padding-bottom: var(--space-4); border-bottom: 2px solid var(--color-border);">
                        <h2 style="margin: 0; color: var(--color-text);">🎉 Completed</h2>
                        <span class="pill" style="background: var(--color-success-soft); color: var(--color-success);"><?= count($completedTasks) ?> task<?= count($completedTasks) !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="card-list">
                        <?php foreach ($completedTasks as $task): ?>
                            <?php require BASE_PATH . '/app/views/tasks/_task-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
