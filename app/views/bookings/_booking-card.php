<?php
$bookingStatusMap = [
    'active' => 'Booked',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
$status = (string) $booking['status'];
$label = $bookingStatusMap[$status] ?? ucfirst($status);
$location = e((string) $booking['city']) . (!empty($booking['region']) ? ', ' . e((string) $booking['region']) : '') . ', ' . e((string) $booking['country']);
$agreement = $agreementsByBooking[(int) ($booking['id'] ?? 0)] ?? null;
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
            <span class="muted">Keep messages, status, agreement records, and issue logs tied to this booking.</span>
            <?php if (is_array($agreement)): ?>
                <span class="pill <?= (string) $agreement['status'] === 'accepted' ? 'pill-success' : ((string) $agreement['status'] === 'disputed' ? 'pill-warning' : 'pill-info') ?>">
                    <?= e(agreement_status_label((string) $agreement['status'])) ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="button-group">
            <?php if (is_array($agreement)): ?>
                <a class="button button-small" href="<?= e(url_for('agreements/review', ['id' => (int) $agreement['id']])) ?>">
                    Review Agreement
                </a>
            <?php endif; ?>
            <a class="button button-secondary button-small" href="<?= e(url_for('bookings/show', ['id' => (int) $booking['id']])) ?>">
                View booking
            </a>
        </div>
    </div>
</article>
