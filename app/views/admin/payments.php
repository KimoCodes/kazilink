<div class="container">
    <section class="panel">
        <?php
        $title = 'Payments';
        $eyebrow = 'Admin';
        $intro = 'Review locally recorded Stripe Checkout sessions, webhook results, and payment statuses.';
        $primaryAction = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($secondaryAction, $secondaryLink);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="admin-kpi-grid">
            <article class="admin-kpi-card admin-kpi-card-primary">
                <span class="admin-kpi-label">Paid Payments</span>
                <strong><?= e((string) $stats['paid_payments']) ?></strong>
            </article>
            <article class="admin-kpi-card admin-kpi-card-info">
                <span class="admin-kpi-label">Payment Volume</span>
                <strong><?= e(moneyRwf($stats['payments_volume'])) ?></strong>
            </article>
        </div>
    </section>

    <section class="panel">
        <?php if ($payments === []): ?>
            <?php
            $emptyIcon = '💳';
            $emptyTitle = 'No payment records yet';
            $emptyMessage = 'Run the Stripe Checkout flow and send webhook events to start populating this page.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Plan</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Internal Status</th>
                            <th>Stripe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= e(dateFmt((string) ($payment['paid_at'] ?? $payment['created_at']))) ?></td>
                                <td>
                                    <strong><?= e((string) $payment['plan_name']) ?></strong><br>
                                    <span class="text-muted"><?= e((string) $payment['plan_id']) ?></span>
                                </td>
                                <td>
                                    <?= e((string) ($payment['full_name'] ?: $payment['customer_email'] ?: 'Guest checkout')) ?><br>
                                    <span class="text-muted text-mono"><?= e((string) $payment['checkout_session_id']) ?></span>
                                </td>
                                <td><?= e(moneyRwf((int) $payment['amount_minor'])) ?></td>
                                <td>
                                    <?php
                                    $status = (string) $payment['status'];
                                    $label = ucfirst((string) $payment['status']);
                                    require BASE_PATH . '/app/views/partials/status-badge.php';
                                    ?>
                                </td>
                                <td>
                                    <span class="entity-chip"><?= e((string) ($payment['stripe_payment_status'] ?: 'n/a')) ?></span>
                                    <div class="text-muted"><?= e((string) ($payment['last_event_type'] ?: 'checkout_created')) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
