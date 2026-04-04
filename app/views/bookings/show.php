<?php
$bookingStatusMap = [
    'active' => 'Booked',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
$status = (string) $booking['status'];
$label = $bookingStatusMap[$status] ?? ucfirst($status);
$location = (string) $booking['city'] . (!empty($booking['region']) ? ', ' . (string) $booking['region'] : '') . ', ' . (string) $booking['country'];
$agreedAmount = (float) ($booking['agreed_amount'] ?? $booking['budget'] ?? 0);
$bookingPayment = is_array($bookingPayment ?? null) ? $bookingPayment : null;
$paymentStatus = (string) ($bookingPayment['status'] ?? '');
$isPaid = $paymentStatus === 'paid';
?>
<div class="container">
    <section class="booking-layout">
        <article class="panel detail-body">
            <?php
            $title = (string) $booking['title'];
            $eyebrow = 'Booking Details';
            $intro = 'Review the confirmed job, keep communication in one thread, and close the booking when done.';
            $primaryAction = ['label' => 'Open messages', 'href' => url_for('messages/thread', ['id' => (int) $booking['id']])];
            $secondaryLink = ['label' => 'Back to bookings', 'href' => url_for('bookings/index')];
            unset($secondaryAction);
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <div class="detail-facts">
                <div class="detail-fact">
                    <span class="detail-fact-label">Status</span>
                    <div class="detail-fact-value"><?php require BASE_PATH . '/app/views/partials/status-badge.php'; ?></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Agreed amount</span>
                    <div class="detail-fact-value"><span class="price"><?= e(moneyRwf($agreedAmount)) ?></span></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Location</span>
                    <div class="detail-fact-value"><?= e($location) ?></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Booked</span>
                    <div class="detail-fact-value"><?= e(dateFmt((string) $booking['booked_at'])) ?></div>
                </div>
            </div>

            <div class="summary-grid">
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Client</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $booking['client_name']) ?></strong>
                        <span>Requested the work</span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Tasker</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $booking['tasker_name']) ?></strong>
                        <span>Confirmed to complete the task</span>
                    </div>
                </article>
            </div>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Description</h2>
                        <p class="section-intro">Everything agreed before work started remains visible here for both sides.</p>
                    </div>
                </div>
                <div class="detail-copy">
                    <?= nl2br(e((string) $booking['description'])) ?>
                </div>
            </section>

            <?php if ($booking['status'] === 'completed'): ?>
                <section class="booking-section">
                    <div class="section-head">
                        <div>
                            <h2>Payment</h2>
                            <p class="section-intro">Completed work should have a clear payment state, not a separate manual follow-up outside the platform.</p>
                        </div>
                    </div>

                    <div class="booking-payment-panel <?= $isPaid ? 'booking-payment-panel-paid' : '' ?>">
                        <div>
                            <span class="sidebar-item-label">Payment status</span>
                            <div class="booking-payment-status-row">
                                <?php
                                $status = $isPaid ? 'paid' : ($paymentStatus !== '' ? $paymentStatus : 'pending');
                                $label = ucfirst($status);
                                require BASE_PATH . '/app/views/partials/status-badge.php';
                                ?>
                                <strong><?= e(moneyRwf($agreedAmount)) ?></strong>
                            </div>
                            <p class="muted">
                                <?php if ($isPaid): ?>
                                    Payment has been recorded for this completed task.
                                <?php elseif (Auth::role() === 'client'): ?>
                                    This completed booking is ready for payment through Stripe Checkout.
                                <?php else: ?>
                                    Waiting for the client to complete payment for this finished booking.
                                <?php endif; ?>
                            </p>
                            <?php if ($bookingPayment !== null): ?>
                                <p class="text-muted text-mono">Ref: <?= e((string) ($bookingPayment['checkout_session_id'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (!$isPaid && Auth::role() === 'client'): ?>
                            <?php if ($paymentsEnabled): ?>
                                <form method="post" action="<?= e(url_for('payments/booking-checkout')) ?>">
                                    <?= Csrf::input() ?>
                                    <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                                    <button type="submit" class="button">Pay for completed task</button>
                                </form>
                            <?php else: ?>
                                <div class="setup-note">
                                    <strong>Stripe setup required</strong>
                                    <p class="muted">Add Stripe keys to `.env` to enable completed-task payments.</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Reviews</h2>
                        <p class="section-intro">Feedback appears here after the booking is completed.</p>
                    </div>
                    <?php if (Auth::role() === 'client' && $booking['status'] === 'completed' && $clientReview === null): ?>
                        <a class="button button-secondary button-small" href="<?= e(url_for('reviews/create', ['booking_id' => (int) $booking['id']])) ?>">
                            Leave review
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($reviews === []): ?>
                    <?php
                    $emptyIcon = '📝';
                    $emptyTitle = 'No reviews yet';
                    $emptyMessage = 'Feedback will appear here once the client leaves a review after completion.';
                    require BASE_PATH . '/app/views/partials/empty_state.php';
                    ?>
                <?php else: ?>
                    <div class="card-list">
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-card">
                                <div class="card-header">
                                    <div>
                                        <h3><?= e((string) $review['reviewer_name']) ?></h3>
                                        <p class="review-meta">
                                            <span>For <?= e((string) $review['reviewee_name']) ?></span>
                                            <span>•</span>
                                            <span><?= e(dateFmt((string) $review['created_at'])) ?></span>
                                        </p>
                                    </div>
                                    <div class="review-stars" aria-label="Rating <?= (int) $review['rating'] ?> out of 5">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <?= $i < (int) $review['rating'] ? '★' : '☆' ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p>
                                    <?= $review['comment'] !== null && $review['comment'] !== '' ? nl2br(e((string) $review['comment'])) : '<span class="text-muted">No comment provided.</span>' ?>
                                </p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (Auth::role() === 'client' && $booking['status'] === 'active'): ?>
                <section class="action-panel">
                    <div>
                        <strong>Mark booking complete?</strong>
                        <p class="muted">Complete the booking after the work is finished to unlock reviews and preserve a clean project history.</p>
                    </div>
                    <form method="post" action="<?= e(url_for('bookings/complete')) ?>">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                        <button type="submit" class="button button-warning" data-confirm="Mark this booking as completed?">
                            Complete booking
                        </button>
                    </form>
                </section>
            <?php endif; ?>
        </article>

        <aside class="sidebar-stack">
            <div class="sidebar-card">
                <span class="sidebar-item-label">Participants</span>
                <div class="sidebar-list">
                    <div>
                        <span class="sidebar-item-label">Client</span>
                        <div class="sidebar-item-value"><?= e((string) $booking['client_name']) ?></div>
                    </div>
                    <div>
                        <span class="sidebar-item-label">Tasker</span>
                        <div class="sidebar-item-value">
                            <?php if (!empty($booking['tasker_id'])): ?>
                                <a href="<?= e(url_for('profile/view', ['id' => (int) $booking['tasker_id']])) ?>">
                                    <?= e((string) $booking['tasker_name']) ?>
                                </a>
                            <?php else: ?>
                                <?= e((string) $booking['tasker_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <span class="sidebar-item-label">Quick Info</span>
                <div class="sidebar-list">
                    <div>
                        <span class="sidebar-item-label">Booking ID</span>
                        <div class="sidebar-item-value"><code class="entity-chip text-mono"><?= e((string) $booking['id']) ?></code></div>
                    </div>
                    <div>
                        <span class="sidebar-item-label">Last status</span>
                        <div class="sidebar-item-value"><?= e($label) ?></div>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <span class="sidebar-item-label">Next step</span>
                <p class="muted">
                    <?php if ($booking['status'] === 'active'): ?>
                        Use messages to confirm timing, access, and any last details before marking the booking complete.
                    <?php elseif ($booking['status'] === 'completed'): ?>
                        Use this page for the final record: agreed scope, payment state, and review history all stay together here.
                    <?php else: ?>
                        This booking has been closed, but the record remains available for reference.
                    <?php endif; ?>
                </p>
            </div>

            <a class="button button-secondary button-block" href="<?= e(url_for('bookings/index')) ?>">
                Back to bookings
            </a>
        </aside>
    </section>
</div>
