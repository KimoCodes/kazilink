<?php
$activeBookings = array_values(array_filter($bookings, static fn (array $booking): bool => (string) $booking['status'] === 'active'));
$completedBookings = array_values(array_filter($bookings, static fn (array $booking): bool => (string) $booking['status'] === 'completed'));
$cancelledBookings = array_values(array_filter($bookings, static fn (array $booking): bool => (string) $booking['status'] === 'cancelled'));
$agreementsByBooking = is_array($agreementsByBooking ?? null) ? $agreementsByBooking : [];
$pagination = is_array($pagination ?? null) ? $pagination : ['total' => count($bookings), 'page' => 1, 'total_pages' => 1];
$totalBookingValue = array_reduce(
    $bookings,
    static fn (float $carry, array $booking): float => $carry + (float) ($booking['agreed_amount'] ?? $booking['budget'] ?? 0),
    0.0
);
$sections = [
    [
        'title' => 'Active',
        'description' => 'Current bookings that still need coordination or completion.',
        'pillClass' => 'pill-info',
        'items' => $activeBookings,
    ],
    [
        'title' => 'Completed',
        'description' => 'Finished work with review history, agreements, and issue records preserved.',
        'pillClass' => 'pill-success',
        'items' => $completedBookings,
    ],
    [
        'title' => 'Cancelled',
        'description' => 'Bookings that were closed before the work was finished.',
        'pillClass' => 'pill-danger',
        'items' => $cancelledBookings,
    ],
];
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'Your bookings';
        $eyebrow = 'Bookings';
        $intro = 'Track confirmed engagements, who they are with, and what stage they are in. Showing ' . count($bookings) . ' of ' . (int) ($pagination['total'] ?? count($bookings)) . '.';
        unset($primaryAction, $secondaryAction, $secondaryLink);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Active bookings</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($activeBookings)) ?></strong>
                    <span>Work currently in progress</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Completed bookings</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($completedBookings)) ?></strong>
                    <span>Jobs already closed out</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Total booking value</span>
                <div class="task-summary-metric-row">
                    <strong><?= e(moneyRwf($totalBookingValue)) ?></strong>
                    <span>Combined value across visible bookings</span>
                </div>
            </article>
        </div>

        <?php if ($bookings === []): ?>
            <?php
            $emptyIcon = '👋';
            $emptyTitle = 'No bookings yet';
            $emptyMessage = 'Confirmed jobs will appear here once a client or tasker accepts a bid.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="section-stack">
                <?php foreach ($sections as $section): ?>
                    <?php if ($section['items'] === []): ?>
                        <?php continue; ?>
                    <?php endif; ?>

                    <section class="booking-section">
                        <div class="section-head">
                            <div>
                                <h2><?= e($section['title']) ?></h2>
                                <p class="section-intro"><?= e($section['description']) ?></p>
                            </div>
                            <span class="pill <?= e($section['pillClass']) ?>">
                                <?= count($section['items']) ?> booking<?= count($section['items']) !== 1 ? 's' : '' ?>
                            </span>
                        </div>

                        <div class="card-list">
                            <?php foreach ($section['items'] as $booking): ?>
                                <?php require BASE_PATH . '/app/views/bookings/_booking-card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <?php
            $paginationRoute = 'bookings/index';
            $paginationParams = [];
            require BASE_PATH . '/app/views/partials/pagination.php';
            ?>
        <?php endif; ?>
    </section>
</div>
