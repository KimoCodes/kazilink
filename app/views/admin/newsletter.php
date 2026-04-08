<?php
$subscriptions = is_array($subscriptions ?? null) ? $subscriptions : [];
$audienceFilter = in_array(($audienceFilter ?? 'all'), ['all', 'client', 'tasker', 'partner'], true) ? $audienceFilter : 'all';
$subscriptionCount = count($subscriptions);
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'Newsletter Subscribers';
        $eyebrow = 'Admin';
        $intro = 'Review locally captured newsletter signups, segment them by audience, and keep outreach organized before a full email provider is connected.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Visible signups</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) $subscriptionCount) ?></strong>
                    <span><?= $audienceFilter === 'all' ? 'Total locally captured newsletter subscribers' : 'Subscribers matching the selected audience filter' ?></span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Capture source</span>
                <div class="task-summary-metric-row">
                    <strong>Footer signup</strong>
                    <span>New entries are captured from the public newsletter form and kept available for follow-up</span>
                </div>
            </article>
        </div>

        <div class="table-toolbar">
            <p>Use the audience filter to prepare segmented outreach without digging through local JSON files.</p>
            <div class="hero-actions">
                <a class="button <?= $audienceFilter === 'all' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter')) ?>">All</a>
                <a class="button <?= $audienceFilter === 'client' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter', ['audience' => 'client'])) ?>">Clients</a>
                <a class="button <?= $audienceFilter === 'tasker' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter', ['audience' => 'tasker'])) ?>">Taskers</a>
                <a class="button <?= $audienceFilter === 'partner' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter', ['audience' => 'partner'])) ?>">Partners</a>
            </div>
        </div>

        <?php if ($subscriptions === []): ?>
            <?php
            $emptyIcon = '✉';
            $emptyTitle = $audienceFilter === 'all' ? 'No newsletter signups yet' : 'No subscribers in this audience yet';
            $emptyMessage = $audienceFilter === 'all'
                ? 'When someone joins from the footer newsletter form, their signup will appear here.'
                : 'Try a different audience filter or wait for new signups from that segment.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Joined</th>
                            <th>Email</th>
                            <th>Audience</th>
                            <th>Source</th>
                            <th>Consent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <?php
                            $payload = is_array($subscription['payload'] ?? null) ? $subscription['payload'] : [];
                            $audience = (string) ($payload['audience'] ?? '');
                            $audienceLabel = (string) ($payload['audience_label'] ?? ucfirst($audience));
                            $pillClass = match ($audience) {
                                'client' => 'pill-primary',
                                'tasker' => 'pill-success',
                                'partner' => 'pill-warning',
                                default => 'pill-info',
                            };
                            ?>
                            <tr>
                                <td><?= e(dateFmt((string) ($subscription['recorded_at'] ?? ''))) ?></td>
                                <td><a href="mailto:<?= e((string) ($payload['email'] ?? '')) ?>"><?= e((string) ($payload['email'] ?? '')) ?></a></td>
                                <td><span class="pill <?= e($pillClass) ?>"><?= e($audienceLabel !== '' ? $audienceLabel : 'General') ?></span></td>
                                <td><?= e((string) ($payload['source_route'] ?? $subscription['route'] ?? 'home/index')) ?></td>
                                <td><?= e((string) ($payload['consent_text'] ?? 'Subscriber asked to receive updates.')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
