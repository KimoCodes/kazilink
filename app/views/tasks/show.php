<?php
$status = (string) $task['status'];
$label = ucfirst($status);
$pendingBids = array_values(array_filter($bids, static fn (array $bid): bool => (string) $bid['status'] === 'pending'));
$acceptedBids = array_values(array_filter($bids, static fn (array $bid): bool => (string) $bid['status'] === 'accepted'));
$rejectedBids = array_values(array_filter($bids, static fn (array $bid): bool => (string) $bid['status'] === 'rejected'));
$lowestBidAmount = $bids !== [] ? min(array_map(static fn (array $bid): float => (float) $bid['amount'], $bids)) : null;
$location = (string) $task['city'] . (!empty($task['region']) ? ', ' . (string) $task['region'] : '') . ', ' . (string) $task['country'];
?>
<div class="container">
    <section class="task-detail-layout">
        <article class="panel detail-body">
            <?php
            $title = (string) $task['title'];
            $eyebrow = 'Task Details';
            $intro = 'Review incoming bids, keep the task brief clear, and book one tasker when you are ready.';
            $primaryAction = $canEdit ? ['label' => 'Edit task', 'href' => url_for('tasks/edit', ['id' => (int) $task['id']])] : null;
            $secondaryLink = ['label' => 'Back to tasks', 'href' => url_for('tasks/index')];
            unset($secondaryAction);
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <div class="detail-facts">
                <div class="detail-fact">
                    <span class="detail-fact-label">Status</span>
                    <div class="detail-fact-value"><?php require BASE_PATH . '/app/views/partials/status-badge.php'; ?></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Budget</span>
                    <div class="detail-fact-value"><span class="price"><?= e(moneyRwf($task['budget'])) ?></span></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Location</span>
                    <div class="detail-fact-value"><?= e($location) ?></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Scheduled</span>
                    <div class="detail-fact-value"><?= e(dateFmt((string) $task['scheduled_for'])) ?></div>
                </div>
            </div>

            <div class="summary-grid">
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Bid overview</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) count($bids)) ?></strong>
                        <span>Total bids received</span>
                    </div>
                    <p class="muted">Pending: <?= count($pendingBids) ?> | Accepted: <?= count($acceptedBids) ?> | Rejected: <?= count($rejectedBids) ?></p>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Lowest current offer</span>
                    <div class="task-summary-metric-row">
                        <strong><?= $lowestBidAmount !== null ? e(moneyRwf($lowestBidAmount)) : 'No bids yet' ?></strong>
                        <span><?= $lowestBidAmount !== null ? 'Best visible price right now' : 'Pricing appears after the first bid' ?></span>
                    </div>
                    <p class="muted">Use the bid list below to compare price and message quality together.</p>
                </article>
            </div>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Description</h2>
                        <p class="section-intro">This is the task brief your bidders are responding to.</p>
                    </div>
                </div>
                <div class="detail-copy">
                    <?= nl2br(e((string) $task['description'])) ?>
                </div>
            </section>

            <?php if ($booking !== null): ?>
                <section class="task-booking-banner">
                    <div>
                        <span class="eyebrow"><?= (string) $task['status'] === 'completed' ? 'Completed Booking' : 'Confirmed Booking' ?></span>
                        <h2><?= (string) $task['status'] === 'completed' ? 'This task has been completed.' : 'This task is booked and in progress.' ?></h2>
                        <p><?= e((string) $booking['tasker_name']) ?> is the confirmed tasker on this job. Use the booking page for status, messaging, and review history.</p>
                    </div>
                    <div class="button-group">
                        <a class="button button-secondary" href="<?= e(url_for('profile/view', ['id' => (int) $booking['tasker_id']])) ?>">View tasker profile</a>
                        <a class="button" href="<?= e(url_for('bookings/show', ['id' => (int) $booking['id']])) ?>">Open booking</a>
                    </div>
                </section>
            <?php endif; ?>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Incoming bids</h2>
                        <p class="section-intro">Compare each offer on price, timing, and message quality before confirming one tasker.</p>
                    </div>
                    <span class="pill pill-info"><?= count($bids) ?> bid<?= count($bids) === 1 ? '' : 's' ?></span>
                </div>

                <?php if ($bids === []): ?>
                    <?php
                    $emptyIcon = '📨';
                    $emptyTitle = 'No bids yet';
                    $emptyMessage = 'As taskers discover your task through browse and filters, their offers will appear here for side-by-side review.';
                    require BASE_PATH . '/app/views/partials/empty_state.php';
                    ?>
                <?php else: ?>
                    <div class="card-list">
                        <?php foreach ($bids as $bid): ?>
                            <?php
                            $bidStatus = (string) $bid['status'];
                            $status = $bidStatus;
                            $label = ucfirst($bidStatus);
                            $isLowestBid = $lowestBidAmount !== null && (float) $bid['amount'] === $lowestBidAmount;
                            ?>
                            <article class="bid-card task-bid-card">
                                <div class="card-header">
                                    <div>
                                        <h3><?= e((string) $bid['tasker_name']) ?></h3>
                                        <p class="inline-meta">
                                            <span><?= e((string) $bid['tasker_email']) ?></span>
                                            <span>•</span>
                                            <span>Submitted <?= e(dateFmt((string) $bid['created_at'])) ?></span>
                                        </p>
                                    </div>
                                    <div class="button-group task-bid-badges">
                                        <?php if ($isLowestBid): ?>
                                            <span class="pill pill-success">Lowest bid</span>
                                        <?php endif; ?>
                                        <?php require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                                    </div>
                                </div>

                                <div class="summary-grid task-bid-summary">
                                    <div class="detail-fact">
                                        <span class="detail-fact-label">Bid amount</span>
                                        <div class="detail-fact-value"><span class="price"><?= e(moneyRwf($bid['amount'])) ?></span></div>
                                    </div>
                                    <div class="detail-fact">
                                        <span class="detail-fact-label">Message quality</span>
                                        <div class="detail-fact-value"><?= $bid['message'] !== null && $bid['message'] !== '' ? 'Included' : 'No note added' ?></div>
                                    </div>
                                </div>

                                <div class="task-bid-message">
                                    <span class="sidebar-item-label">Tasker message</span>
                                    <?php if ($bid['message'] !== null && $bid['message'] !== ''): ?>
                                        <p><?= nl2br(e((string) $bid['message'])) ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No message included with this bid.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="task-bid-footer">
                                    <a class="button button-secondary button-small" href="<?= e(url_for('profile/view', ['id' => (int) $bid['tasker_id']])) ?>">View profile</a>

                                    <?php if ($task['status'] === 'open' && $bid['status'] === 'pending'): ?>
                                        <form method="post" action="<?= e(url_for('bids/accept')) ?>">
                                            <?= Csrf::input() ?>
                                            <input type="hidden" name="bid_id" value="<?= e((string) $bid['id']) ?>">
                                            <button type="submit" class="button button-small" data-confirm="Book this tasker and create a booking?">
                                                Accept bid
                                            </button>
                                        </form>
                                    <?php elseif ($bid['status'] === 'accepted'): ?>
                                        <span class="pill pill-success">Booked tasker</span>
                                    <?php else: ?>
                                        <span class="text-muted">No action available</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($canEdit && $task['status'] === 'open'): ?>
                <section class="action-panel">
                    <div>
                        <strong>Need to close this task?</strong>
                        <p class="muted">Cancelling removes it from discovery and stops the bidding process without changing your past booking history.</p>
                    </div>
                    <form method="post" action="<?= e(url_for('tasks/cancel')) ?>">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="id" value="<?= e((string) $task['id']) ?>">
                        <button type="submit" class="button button-danger" data-confirm="Cancel this task? All bids will be withdrawn.">
                            Cancel task
                        </button>
                    </form>
                </section>
            <?php endif; ?>
        </article>

        <aside class="sidebar-stack">
            <div class="sidebar-card">
                <span class="sidebar-item-label">Client controls</span>
                <div class="sidebar-list">
                    <div>
                        <span class="sidebar-item-label">Posted</span>
                        <div class="sidebar-item-value"><?= e(dateFmt((string) $task['created_at'])) ?></div>
                    </div>
                    <div>
                        <span class="sidebar-item-label">Task ID</span>
                        <div class="sidebar-item-value"><code class="entity-chip text-mono">#<?= e((string) $task['id']) ?></code></div>
                    </div>
                    <div>
                        <span class="sidebar-item-label">Country</span>
                        <div class="sidebar-item-value"><?= e((string) $task['country']) ?></div>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <span class="sidebar-item-label">Next best action</span>
                <p class="muted">
                    <?php if ($booking !== null): ?>
                        Open the booking to manage messages, completion, and reviews from the confirmed task thread.
                    <?php elseif ($bids !== []): ?>
                        Compare the bids below and accept the tasker that feels strongest on fit, not just price.
                    <?php else: ?>
                        Leave the task active so taskers can continue discovering and bidding on it.
                    <?php endif; ?>
                </p>
            </div>
        </aside>
    </section>
</div>
