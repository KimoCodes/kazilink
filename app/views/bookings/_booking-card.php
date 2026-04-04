<?php
$bookingStatusMap = [
    'active' => 'Booked',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
$status = (string) $booking['status'];
$label = $bookingStatusMap[$status] ?? ucfirst($status);
$location = e((string) $booking['city']) . (!empty($booking['region']) ? ', ' . e((string) $booking['region']) : '') . ', ' . e((string) $booking['country']);
$bookingPayment = $paymentsByBooking[(int) ($booking['id'] ?? 0)] ?? null;
$paymentStatus = is_array($bookingPayment) ? (string) ($bookingPayment['status'] ?? '') : '';
$isPaid = $paymentStatus === 'paid';
$agreedAmount = (float) ($booking['agreed_amount'] ?? $booking['budget'] ?? 0);
?>
<article class="booking-card">
    <div class="card-header">
        <div>
            <h3 class="booking-card-title"><?= e((string) $booking['title']) ?></h3>
            <p class="booking-card-meta">
                <span><?= e(dateFmt((string) $booking['booked_at'])) ?></span>
                <span>•</span>
                <span><?= $location ?></span>
            </p>
        </div>
        <div class="booking-card-status">
            <?php require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
        </div>
    </div>

    <ul class="summary-list booking-card-summary">
        <li>
            <strong>Client</strong>
            <span class="sidebar-item-value"><?= e((string) $booking['client_name']) ?></span>
        </li>
        <li>
            <strong>Tasker</strong>
            <span class="sidebar-item-value">
                <?php if (!empty($booking['tasker_id'])): ?>
                    <a class="booking-card-link" href="<?= e(url_for('profile/view', ['id' => (int) $booking['tasker_id']])) ?>">
                        <?= e((string) $booking['tasker_name']) ?>
                    </a>
                <?php else: ?>
                    <?= e((string) $booking['tasker_name']) ?>
                <?php endif; ?>
            </span>
        </li>
        <li>
            <strong>Agreed amount</strong>
            <span class="price"><?= e(moneyRwf($agreedAmount)) ?></span>
        </li>
    </ul>

    <div class="booking-card-footer">
        <div class="booking-card-footer-copy">
            <span class="muted">Keep messages, status, reviews, and payment tied to this booking.</span>
            <?php if ($status === 'completed'): ?>
                <span class="pill <?= $isPaid ? 'pill-success' : 'pill-warning' ?>">
                    <?= e($isPaid ? 'Paid' : 'Payment pending') ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="button-group">
            <?php if ($status === 'completed' && !$isPaid && Auth::role() === 'client' && $paymentsEnabled): ?>
                <form method="post" action="<?= e(url_for('payments/booking-checkout')) ?>">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                    <button type="submit" class="button button-small">Pay now</button>
                </form>
            <?php endif; ?>
            <a class="button button-secondary button-small" href="<?= e(url_for('bookings/show', ['id' => (int) $booking['id']])) ?>">
                View booking
            </a>
        </div>
    </div>
</article>
