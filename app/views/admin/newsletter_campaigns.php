<?php
$campaigns = is_array($campaigns ?? null) ? $campaigns : [];
$subscriberCounts = is_array($subscriberCounts ?? null) ? $subscriberCounts : [];
$currentStatus = $currentStatus ?? null;
$pagination = is_array($pagination ?? null) ? $pagination : ['total' => count($campaigns), 'page' => 1, 'total_pages' => 1];
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'Newsletter Campaigns';
        $eyebrow = 'Admin';
        $intro = 'Create and manage email campaigns to send updates and announcements to newsletter subscribers.';
        $primaryAction = ['label' => 'Create Campaign', 'href' => url_for('admin/newsletter-campaigns/create')];
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Total Campaigns</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($campaigns)) ?></strong>
                    <span><?= $currentStatus ? ucfirst($currentStatus) . ' campaigns' : 'All campaigns' ?></span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Total Subscribers</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) ($subscriberCounts['all'] ?? 0)) ?></strong>
                    <span>Active newsletter subscribers</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Client Subscribers</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) ($subscriberCounts['client'] ?? 0)) ?></strong>
                    <span>Interested in hiring services</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Tasker Subscribers</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) ($subscriberCounts['tasker'] ?? 0)) ?></strong>
                    <span>Interested in work opportunities</span>
                </div>
            </article>
        </div>

        <div class="table-toolbar">
            <p>Filter campaigns by status or create new campaigns to engage with your audience.</p>
            <div class="hero-actions">
                <a class="button <?= $currentStatus === null ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter-campaigns')) ?>">All</a>
                <a class="button <?= $currentStatus === 'draft' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter-campaigns', ['status' => 'draft'])) ?>">Draft</a>
                <a class="button <?= $currentStatus === 'scheduled' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter-campaigns', ['status' => 'scheduled'])) ?>">Scheduled</a>
                <a class="button <?= $currentStatus === 'sent' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter-campaigns', ['status' => 'sent'])) ?>">Sent</a>
                <a class="button <?= $currentStatus === 'failed' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/newsletter-campaigns', ['status' => 'failed'])) ?>">Failed</a>
            </div>
        </div>

        <?php if ($campaigns === []): ?>
            <?php
            $emptyIcon = 'campaign';
            $emptyTitle = 'No campaigns found';
            $emptyMessage = $currentStatus 
                ? 'No campaigns with status "' . ucfirst($currentStatus) . '". Try a different filter or create a new campaign.'
                : 'No campaigns yet. Create your first newsletter campaign to start engaging with subscribers.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="hide-mobile">Created By Email</th>
                            <th class="hide-mobile">Performance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <?php
                            $statusClass = match ($campaign['status']) {
                                'draft' => 'pill-info',
                                'scheduled' => 'pill-warning',
                                'sending' => 'pill-warning',
                                'sent' => 'pill-success',
                                'failed' => 'pill-danger',
                                'cancelled' => 'pill-danger',
                                default => 'pill-info',
                            };

                            $audienceClass = match ($campaign['audience']) {
                                'all' => 'pill-info',
                                'client' => 'pill-primary',
                                'tasker' => 'pill-success',
                                'partner' => 'pill-warning',
                                default => 'pill-info',
                            };

                            $totalSubscribers = (int) ($campaign['total_subscribers'] ?? 0);
                            $sentCount = (int) ($campaign['sent_count'] ?? 0);
                            $deliveredCount = (int) ($campaign['delivered_count'] ?? 0);
                            $failedCount = (int) ($campaign['failed_count'] ?? 0);

                            $performanceText = '';
                            if ($campaign['status'] === 'sent' && $totalSubscribers > 0) {
                                $successRate = round(($deliveredCount / $totalSubscribers) * 100, 1);
                                $performanceText = "{$deliveredCount}/{$totalSubscribers} ({$successRate}%)";
                            } elseif ($campaign['status'] === 'sent' && $totalSubscribers === 0) {
                                $performanceText = 'No subscribers';
                            } elseif (in_array($campaign['status'], ['scheduled', 'sending'])) {
                                $performanceText = 'Pending';
                            } elseif ($campaign['status'] === 'failed') {
                                $performanceText = "{$failedCount} failed";
                            }
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url_for('admin/newsletter-campaigns/show', ['id' => $campaign['id']])) ?>">
                                        <?= e($campaign['title']) ?>
                                    </a>
                                </td>
                                <td><?= e($campaign['subject']) ?></td>
                                <td><span class="pill <?= e($audienceClass) ?>"><?= e(ucfirst($campaign['audience'])) ?></span></td>
                                <td><span class="pill <?= e($statusClass) ?>"><?= e(ucfirst($campaign['status'])) ?></span></td>
                                <td><?= e(dateFmt($campaign['created_at'])) ?></td>
                                <td class="hide-mobile"><?= e($campaign['created_by_email'] ?? 'Unknown') ?></td>
                                <td class="hide-mobile"><?= e($performanceText) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="<?= e(url_for('admin/newsletter-campaigns/show', ['id' => $campaign['id']])) ?>" class="button button-small">View</a>
                                        
                                        <?php if (in_array($campaign['status'], ['draft', 'failed'])): ?>
                                            <a href="<?= e(url_for('admin/newsletter-campaigns/edit', ['id' => $campaign['id']])) ?>" class="button button-secondary button-small">Edit</a>
                                        <?php endif; ?>

                                        <?php if ($campaign['status'] === 'draft'): ?>
                                            <form method="post" action="<?= e(url_for('admin/newsletter-campaigns/schedule')) ?>" class="table-inline-form">
                                                <?= Csrf::input() ?>
                                                <input type="hidden" name="campaign_id" value="<?= e((string) $campaign['id']) ?>">
                                                <button type="submit" class="button button-secondary button-small" data-confirm="Schedule this campaign for sending?">Schedule</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($campaign['status'] === 'scheduled'): ?>
                                            <form method="post" action="<?= e(url_for('admin/newsletter-campaigns/send')) ?>" class="table-inline-form">
                                                <?= Csrf::input() ?>
                                                <input type="hidden" name="campaign_id" value="<?= e((string) $campaign['id']) ?>">
                                                <button type="submit" class="button button-small" data-confirm="Send this campaign now?">Send Now</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (in_array($campaign['status'], ['draft', 'failed'])): ?>
                                            <form method="post" action="<?= e(url_for('admin/newsletter-campaigns/delete')) ?>" class="table-inline-form">
                                                <?= Csrf::input() ?>
                                                <input type="hidden" name="campaign_id" value="<?= e((string) $campaign['id']) ?>">
                                                <button type="submit" class="button button-danger button-small" data-confirm="Delete this campaign permanently?">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
                <?php require BASE_PATH . '/app/views/partials/pagination.php'; ?>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
