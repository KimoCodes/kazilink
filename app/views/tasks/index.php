<div class="container">
    <?php
    $agreementsByBooking = is_array($agreementsByBooking ?? null) ? $agreementsByBooking : [];
    $availableTaskers = is_array($availableTaskers ?? null) ? $availableTaskers : [];
    $ads = is_array($ads ?? null) ? $ads : [];
    ?>
    <section class="panel">
        <?php
        // Prepare variables for page_header partial
        $title = 'My tasks';
        $eyebrow = 'Client Workspace';
        $intro = 'Track what is open, what is booked, and which hires already have protected agreement records.';
        $primaryAction = [
            'label' => '+ Post a task',
            'href' => url_for('tasks/create')
        ];
        $secondaryAction = [
            'label' => 'Find taskers',
            'href' => url_for('tasks/index') . '#available-taskers'
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

    <?php
    if ($ads !== []) {
        require BASE_PATH . '/app/views/partials/ad-banner.php';
    }
    ?>

    <section class="panel panel-subtle" id="available-taskers">
        <div class="section-head">
            <div>
                <span class="eyebrow">Available workers</span>
                <h2>Compare taskers before you hire</h2>
                <p class="section-intro">Use public ratings, completed job history, and profile summaries to identify strong candidates before you accept a bid.</p>
            </div>
            <span class="pill pill-info"><?= count($availableTaskers) ?> visible tasker<?= count($availableTaskers) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($availableTaskers === []): ?>
            <?php
            $emptyIcon = '👷';
            $emptyTitle = 'No taskers available yet';
            $emptyMessage = 'Active worker profiles will appear here with ratings and completed job counts.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="home-category-grid">
                <?php foreach ($availableTaskers as $tasker): ?>
                    <?php
                    $reviewCount = (int) ($tasker['review_count'] ?? 0);
                    $avgRating = (float) ($tasker['avg_rating'] ?? 0);
                    $completedJobs = (int) ($tasker['completed_jobs'] ?? 0);
                    $locationParts = array_values(array_filter([
                        (string) ($tasker['city'] ?? ''),
                        (string) ($tasker['region'] ?? ''),
                        (string) ($tasker['country'] ?? ''),
                    ], static fn (string $value): bool => trim($value) !== ''));
                    $locationLabel = $locationParts !== [] ? implode(', ', $locationParts) : 'Location not added yet';
                    $summary = trim((string) ($tasker['skills_summary'] ?? '')) !== ''
                        ? (string) $tasker['skills_summary']
                        : ((trim((string) ($tasker['bio'] ?? '')) !== '') ? (string) $tasker['bio'] : 'Profile details are still being completed.');
                    ?>
                    <article class="home-category-card">
                        <div class="button-group">
                            <span class="pill pill-success"><?= $reviewCount > 0 ? e(number_format($avgRating, 1)) . '/5' : 'No ratings yet' ?></span>
                            <span class="pill pill-info"><?= e((string) $reviewCount) ?> review<?= $reviewCount === 1 ? '' : 's' ?></span>
                            <span class="pill pill-primary"><?= e((string) $completedJobs) ?> completed</span>
                        </div>
                        <h3><?= e((string) ($tasker['full_name'] ?: $tasker['email'])) ?></h3>
                        <p class="muted"><?= e($locationLabel) ?></p>
                        <p><?= e(mb_strimwidth($summary, 0, 140, '...')) ?></p>
                        <a class="button-link" href="<?= e(url_for('profile/view', ['id' => (int) $tasker['id']])) ?>">View ratings and profile</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
