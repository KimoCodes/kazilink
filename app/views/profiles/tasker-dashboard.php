<div class="container">
    <section class="panel">
        <?php
        $title = 'Tasker Dashboard';
        $eyebrow = 'Dashboard';
        $intro = 'Track your applications, bookings, and performance as a tasker.';
        $primaryAction = ['label' => 'Browse tasks', 'href' => url_for('tasks/browse')];
        $secondaryAction = ['label' => 'Edit profile', 'href' => url_for('profile/edit')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <!-- Stats Overview -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-8); padding: var(--space-5); background: var(--color-surface-muted); border-radius: var(--radius-lg);">
            <div>
                <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-1);">Total bids</span>
                <strong style="font-size: 1.5rem; color: var(--color-primary-strong);">
                    <?= (int) $stats['total_bids'] ?>
                </strong>
            </div>
            <div>
                <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-1);">Active bids</span>
                <strong style="font-size: 1.5rem; color: var(--color-info);">
                    <?= (int) $stats['active_bids'] ?>
                </strong>
            </div>
            <div>
                <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-1);">Completed jobs</span>
                <strong style="font-size: 1.5rem; color: var(--color-success);">
                    <?= (int) $stats['completed_bookings'] ?>
                </strong>
            </div>
            <div>
                <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-1);">Total earnings</span>
                <strong style="font-size: 1.5rem; color: var(--color-primary-strong);">
                    <?= e(moneyRwf($stats['total_earnings'])) ?>
                </strong>
            </div>
            <div>
                <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-1);">Average rating</span>
                <strong style="font-size: 1.5rem; color: var(--color-warning);">
                    <?= number_format((float) $stats['average_rating'], 1) ?>/5
                </strong>
            </div>
            <div>
                <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-1);">Reviews</span>
                <strong style="font-size: 1.5rem; color: var(--color-text);">
                    <?= (int) $stats['review_count'] ?>
                </strong>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
            <!-- Active Bids Section -->
            <div>
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); padding-bottom: var(--space-4); border-bottom: 2px solid var(--color-border);">
                    <h2 style="margin: 0; color: var(--color-text);">📝 Active applications</h2>
                    <span class="pill" style="background: var(--color-info-soft); color: var(--color-info);"><?= count($activeBids) ?> active</span>
                </div>

                <?php if ($activeBids === []): ?>
                    <?php
                    $emptyIcon = '📝';
                    $emptyTitle = 'No active applications';
                    $emptyMessage = 'You do not have any pending or accepted bids right now.';
                    $emptyAction = ['label' => 'Browse available tasks', 'href' => url_for('tasks/browse'), 'class' => 'button'];
                    require BASE_PATH . '/app/views/partials/empty_state.php';
                    ?>
                <?php else: ?>
                    <div class="card-list">
                        <?php foreach ($activeBids as $bid): ?>
                            <?php
                            $activeBidHref = ((string) $bid['status'] === 'accepted' && !empty($bid['booking_id']))
                                ? url_for('bookings/show', ['id' => (int) $bid['booking_id']])
                                : url_for('tasks/view', ['id' => (int) $bid['task_id']]);
                            ?>
                            <article style="padding: var(--space-4); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-3); margin-bottom: var(--space-3);">
                                    <div style="flex: 1; min-width: 0;">
                                        <h3 style="margin: 0 0 var(--space-2) 0; font-size: var(--font-lg); line-height: 1.4;">
                                            <a href="<?= e($activeBidHref) ?>" style="color: var(--color-text); text-decoration: none;">
                                                <?= e((string) $bid['task_title']) ?>
                                            </a>
                                        </h3>
                                        <div style="display: flex; gap: var(--space-2); align-items: center; margin-bottom: var(--space-2); color: var(--color-text-muted); font-size: var(--font-sm);">
                                            <span>👤 <?= e((string) $bid['client_name']) ?></span>
                                            <span>•</span>
                                            <span>📍 <?= e((string) $bid['city']) ?>, <?= e((string) $bid['country']) ?></span>
                                        </div>
                                        <div style="color: var(--color-text-muted); font-size: var(--font-sm);">
                                            Bid: <strong style="color: var(--color-primary);"><?= e(moneyRwf($bid['amount'])) ?></strong>
                                            (Task budget: <?= e(moneyRwf($bid['budget'])) ?>)
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <?php $status = (string) $bid['status']; $label = ucfirst((string) $bid['status']); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                                    </div>
                                </div>
                                <?php if (!empty($bid['message'])): ?>
                                    <p style="margin: 0; color: var(--color-text-muted); font-size: var(--font-sm); font-style: italic;">
                                        "<?= e((string) $bid['message']) ?>"
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Bookings Section -->
            <div>
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); padding-bottom: var(--space-4); border-bottom: 2px solid var(--color-border);">
                    <h2 style="margin: 0; color: var(--color-text);">📋 Recent bookings</h2>
                    <span class="pill" style="background: var(--color-primary-soft); color: var(--color-primary);"><?= count($recentBookings) ?> recent</span>
                </div>

                <?php if ($recentBookings === []): ?>
                    <?php
                    $emptyIcon = '📋';
                    $emptyTitle = 'No bookings yet';
                    $emptyMessage = 'Your completed and active jobs will appear here.';
                    $emptyAction = ['label' => 'View all bookings', 'href' => url_for('bookings/index')];
                    require BASE_PATH . '/app/views/partials/empty_state.php';
                    ?>
                <?php else: ?>
                    <div class="card-list">
                        <?php foreach ($recentBookings as $booking): ?>
                            <article style="padding: var(--space-4); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-3); margin-bottom: var(--space-3);">
                                    <div style="flex: 1; min-width: 0;">
                                        <h3 style="margin: 0 0 var(--space-2) 0; font-size: var(--font-lg); line-height: 1.4;">
                                            <a href="<?= e(url_for('bookings/show', ['id' => (int) $booking['id']])) ?>" style="color: var(--color-text); text-decoration: none;">
                                                <?= e((string) $booking['title']) ?>
                                            </a>
                                        </h3>
                                        <div style="display: flex; gap: var(--space-2); align-items: center; margin-bottom: var(--space-2); color: var(--color-text-muted); font-size: var(--font-sm);">
                                            <span>👤 <?= e((string) $booking['client_name']) ?></span>
                                            <span>•</span>
                                            <span>📍 <?= e((string) $booking['city']) ?>, <?= e((string) $booking['country']) ?></span>
                                        </div>
                                        <div style="color: var(--color-text-muted); font-size: var(--font-sm);">
                                            <strong style="color: var(--color-primary);"><?= e(moneyRwf($booking['budget'])) ?></strong>
                                            • Booked <?= e(dateFmt($booking['booked_at'])) ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <?php
                                        $bookingStatusMap = [
                                            'active' => 'Booked',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled',
                                        ];
                                        $status = (string) $booking['status'];
                                        $label = $bookingStatusMap[$status] ?? ucfirst($status);
                                        require BASE_PATH . '/app/views/partials/status-badge.php';
                                        ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: var(--space-4);">
                        <a class="button button-secondary button-small" href="<?= e(url_for('bookings/index')) ?>">View all bookings</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
