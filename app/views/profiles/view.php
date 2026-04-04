<?php
$locationParts = array_values(array_filter([
    (string) ($profile['city'] ?? ''),
    (string) ($profile['region'] ?? ''),
    (string) ($profile['country'] ?? ''),
], static fn (string $value): bool => trim($value) !== ''));
$locationLabel = $locationParts !== [] ? implode(', ', $locationParts) : 'Rwanda';
$avatarUrl = !empty($profile['avatar_path']) ? public_url((string) $profile['avatar_path']) : null;
$displayName = (string) $profile['full_name'];
$avatarInitial = strtoupper(substr($displayName !== '' ? $displayName : 'T', 0, 1));
$reviewCount = (int) ($reviewStats['review_count'] ?? 0);
$averageRating = (float) ($reviewStats['average_rating'] ?? 0);
$completedJobs = (int) ($bookingStats['completed_jobs'] ?? 0);
$completionRate = (string) ($bookingStats['completion_rate'] ?? '0%');
$lastCompletedAt = $bookingStats['last_completed_at'] ?? null;
$sortLabel = $sort === 'highest' ? 'highest rating' : ($sort === 'lowest' ? 'lowest rating' : 'newest');
$currentRole = (string) Auth::role();
?>
<div class="container">
    <section class="task-detail-layout">
        <article class="panel detail-body">
            <?php
            $title = $displayName;
            $eyebrow = 'Tasker Profile';
            $intro = 'Review recent client feedback, completed work, and core profile details before you decide who to trust with the job.';
            if ($currentRole === 'client') {
                $primaryAction = ['label' => 'Post a new task', 'href' => url_for('tasks/create')];
            } elseif ($currentRole === 'tasker') {
                $primaryAction = ['label' => 'Browse tasks', 'href' => url_for('tasks/browse')];
            } else {
                $primaryAction = ['label' => 'Back home', 'href' => url_for('home/index')];
            }
            $secondaryLink = ['label' => 'Back to home', 'href' => url_for('home/index')];
            unset($secondaryAction);
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <section class="profile-hero">
                <div class="profile-identity">
                    <div class="profile-avatar-wrap">
                        <?php if ($avatarUrl !== null): ?>
                            <button
                                type="button"
                                class="profile-avatar-button"
                                data-avatar-modal-src="<?= e($avatarUrl) ?>"
                                data-avatar-modal-alt="<?= e($displayName !== '' ? $displayName : 'Tasker avatar') ?>"
                                aria-label="Open tasker photo"
                            >
                                <img class="profile-avatar-xl" src="<?= e($avatarUrl) ?>" alt="<?= e($displayName) ?> avatar">
                            </button>
                        <?php else: ?>
                            <div class="profile-avatar-xl profile-avatar-fallback profile-avatar-xl-fallback"><?= e($avatarInitial) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-identity-copy">
                        <div class="button-group">
                            <span class="pill pill-success">Verified tasker</span>
                            <span class="pill pill-info"><?= e((string) $reviewCount) ?> review<?= $reviewCount === 1 ? '' : 's' ?></span>
                            <span class="pill pill-primary"><?= e($completionRate) ?> completion rate</span>
                        </div>

                        <div class="profile-heading-block">
                            <h2><?= e($displayName) ?></h2>
                            <p class="muted"><?= e($locationLabel) ?></p>
                        </div>

                        <div class="profile-meta-grid">
                            <div class="profile-meta-item">
                                <span class="sidebar-item-label">Email</span>
                                <div class="sidebar-item-value"><?= !empty($profile['email']) ? e((string) $profile['email']) : 'Not shared' ?></div>
                            </div>
                            <div class="profile-meta-item">
                                <span class="sidebar-item-label">Phone</span>
                                <div class="sidebar-item-value"><?= !empty($profile['phone']) ? e((string) $profile['phone']) : 'Not shared' ?></div>
                            </div>
                            <div class="profile-meta-item">
                                <span class="sidebar-item-label">Skills</span>
                                <div class="sidebar-item-value"><?= !empty($profile['skills_summary']) ? e((string) $profile['skills_summary']) : 'General task support' ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="summary-grid">
                    <article class="info-card task-summary-card">
                        <span class="sidebar-item-label">Average rating</span>
                        <div class="task-summary-metric-row">
                            <strong><?= $reviewCount > 0 ? e(number_format($averageRating, 1)) . '/5' : 'No ratings yet' ?></strong>
                            <span><?= $reviewCount > 0 ? e((string) $reviewCount) . ' client review' . ($reviewCount === 1 ? '' : 's') : 'Ratings appear after completed bookings' ?></span>
                        </div>
                    </article>
                    <article class="info-card task-summary-card">
                        <span class="sidebar-item-label">Completed jobs</span>
                        <div class="task-summary-metric-row">
                            <strong><?= e((string) $completedJobs) ?></strong>
                            <span>Jobs closed successfully through the booking flow</span>
                        </div>
                    </article>
                    <article class="info-card task-summary-card">
                        <span class="sidebar-item-label">Last completed job</span>
                        <div class="task-summary-metric-row">
                            <strong><?= $lastCompletedAt !== null ? e(dateFmt((string) $lastCompletedAt)) : 'No completed jobs yet' ?></strong>
                            <span>Useful when clients want a sense of recent activity</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>About this tasker</h2>
                        <p class="section-intro">A strong public profile helps clients compare fit, not just price.</p>
                    </div>
                </div>
                <?php if (!empty($profile['bio'])): ?>
                    <div class="detail-copy">
                        <?= nl2br(e((string) $profile['bio'])) ?>
                    </div>
                <?php else: ?>
                    <?php
                    $emptyIcon = '👤';
                    $emptyTitle = 'Profile summary coming soon';
                    $emptyMessage = 'This tasker has not written a public biography yet, so use reviews and booking history as the main trust signals.';
                    require BASE_PATH . '/app/views/partials/empty_state.php';
                    ?>
                <?php endif; ?>
            </section>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Recent reviews</h2>
                        <p class="section-intro">Sorted by <?= e($sortLabel) ?> so clients can inspect quality from multiple angles.</p>
                    </div>
                </div>

                <div class="button-group">
                    <a class="button button-secondary button-small" href="<?= e(url_for('profile/view', ['id' => (int) $profile['user_id'], 'sort' => 'newest'])) ?>">Newest</a>
                    <a class="button button-secondary button-small" href="<?= e(url_for('profile/view', ['id' => (int) $profile['user_id'], 'sort' => 'highest'])) ?>">Highest rating</a>
                    <a class="button button-secondary button-small" href="<?= e(url_for('profile/view', ['id' => (int) $profile['user_id'], 'sort' => 'lowest'])) ?>">Lowest rating</a>
                </div>

                <?php if (empty($reviews)): ?>
                    <?php
                    $emptyIcon = '⭐';
                    $emptyTitle = 'No public reviews yet';
                    $emptyMessage = 'This profile is live, but client reviews will only appear after completed bookings.';
                    require BASE_PATH . '/app/views/partials/empty_state.php';
                    ?>
                <?php else: ?>
                    <div class="card-list">
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-card profile-review-card">
                                <div class="card-header">
                                    <div>
                                        <h3><?= e($review['reviewer_name'] ?: 'Client') ?></h3>
                                        <p class="review-meta">
                                            <span><?= e(dateFmt((string) $review['created_at'])) ?></span>
                                            <span>•</span>
                                            <span><?= e((string) $review['task_title']) ?></span>
                                        </p>
                                    </div>
                                    <div class="review-stars" aria-label="Rating <?= (int) $review['rating'] ?> out of 5">
                                        <?php $stars = (int) $review['rating']; ?>
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <?= $i < $stars ? '★' : '☆' ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <?php if (!empty($review['comment'])): ?>
                                    <div class="profile-rich-copy"><?= nl2br(e((string) $review['comment'])) ?></div>
                                <?php else: ?>
                                    <p class="text-muted">No written comment was provided for this rating.</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </article>

        <aside class="sidebar-stack">
            <div class="sidebar-card">
                <span class="sidebar-item-label">Tasker snapshot</span>
                <div class="sidebar-list">
                    <div>
                        <span class="sidebar-item-label">Location</span>
                        <div class="sidebar-item-value"><?= e($locationLabel) ?></div>
                    </div>
                    <div>
                        <span class="sidebar-item-label">Reviews</span>
                        <div class="sidebar-item-value"><?= e((string) $reviewCount) ?></div>
                    </div>
                    <div>
                        <span class="sidebar-item-label">Completed jobs</span>
                        <div class="sidebar-item-value"><?= e((string) $completedJobs) ?></div>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <span class="sidebar-item-label">How to evaluate</span>
                <p class="muted">Compare this tasker’s pricing, review quality, and profile clarity alongside their bids before confirming a booking.</p>
            </div>

            <?php if ($currentRole === 'client'): ?>
                <a class="button button-block" href="<?= e(url_for('tasks/create')) ?>">Post a task</a>
            <?php else: ?>
                <a class="button button-secondary button-block" href="<?= e(url_for('home/index')) ?>">Back home</a>
            <?php endif; ?>
        </aside>
    </section>
</div>
