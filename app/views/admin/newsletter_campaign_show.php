<?php
$campaign = is_array($campaign ?? null) ? $campaign : [];
$stats = is_array($stats ?? null) ? $stats : [];
$deliveryReport = is_array($deliveryReport ?? null) ? $deliveryReport : [];
$subscribers = is_array($subscribers ?? null) ? $subscribers : [];
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'Campaign Details';
        $eyebrow = 'Admin';
        $intro = 'View detailed information about this email campaign.';
        $secondaryLink = ['label' => 'Back to campaigns', 'href' => url_for('admin/newsletter-campaigns')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="theme-admin-panel">
            <div class="theme-card">
                <div class="theme-card-head">
                    <h3>Campaign Information</h3>
                    <p>Basic details and status of this campaign.</p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div>
                        <strong>Title:</strong><br>
                        <?= e($campaign['title']) ?>
                    </div>
                    <div>
                        <strong>Subject:</strong><br>
                        <?= e($campaign['subject']) ?>
                    </div>
                    <div>
                        <strong>Audience:</strong><br>
                        <span class="pill pill-<?= match($campaign['audience']) {
                            'all' => 'info',
                            'client' => 'primary',
                            'tasker' => 'success',
                            'partner' => 'warning',
                            default => 'info'
                        } ?>"><?= e(ucfirst($campaign['audience'])) ?></span>
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <span class="pill pill-<?= match($campaign['status']) {
                            'draft' => 'info',
                            'scheduled' => 'warning',
                            'sending' => 'warning',
                            'sent' => 'success',
                            'failed' => 'danger',
                            'cancelled' => 'danger',
                            default => 'info'
                        } ?>"><?= e(ucfirst($campaign['status'])) ?></span>
                    </div>
                    <div>
                        <strong>Created:</strong><br>
                        <?= e(dateFmt($campaign['created_at'])) ?>
                    </div>
                    <div>
                        <strong>Created By:</strong><br>
                        <?= e($campaign['created_by_email'] ?? 'Unknown') ?>
                    </div>
                    <?php if ($campaign['scheduled_at']): ?>
                        <div>
                            <strong>Scheduled:</strong><br>
                            <?= e(dateFmt($campaign['scheduled_at'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($campaign['sent_at']): ?>
                        <div>
                            <strong>Sent:</strong><br>
                            <?= e(dateFmt($campaign['sent_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($stats): ?>
                <div class="theme-card">
                    <div class="theme-card-head">
                        <h3>Performance Statistics</h3>
                        <p>Delivery and engagement metrics for this campaign.</p>
                    </div>

                    <div class="summary-grid booking-index-summary">
                        <article class="info-card task-summary-card">
                            <span class="sidebar-item-label">Total Subscribers</span>
                            <div class="task-summary-metric-row">
                                <strong><?= e((string) $stats['total_subscribers']) ?></strong>
                                <span>Target audience members</span>
                            </div>
                        </article>
                        <article class="info-card task-summary-card">
                            <span class="sidebar-item-label">Sent</span>
                            <div class="task-summary-metric-row">
                                <strong><?= e((string) $stats['sent_count']) ?></strong>
                                <span>Successfully sent emails</span>
                            </div>
                        </article>
                        <article class="info-card task-summary-card">
                            <span class="sidebar-item-label">Delivered</span>
                            <div class="task-summary-metric-row">
                                <strong><?= e((string) $stats['delivered_count']) ?></strong>
                                <span>Confirmed deliveries</span>
                            </div>
                        </article>
                        <article class="info-card task-summary-card">
                            <span class="sidebar-item-label">Failed</span>
                            <div class="task-summary-metric-row">
                                <strong><?= e((string) $stats['failed_count']) ?></strong>
                                <span>Failed deliveries</span>
                            </div>
                        </article>
                    </div>

                    <?php if ($stats['total_subscribers'] > 0): ?>
                        <div style="margin-top: 1.5rem;">
                            <strong>Success Rate:</strong> 
                            <?= e(number_format(($stats['delivered_count'] / $stats['total_subscribers']) * 100, 1)) ?>%
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="theme-card">
                <div class="theme-card-head">
                    <h3>Email Content</h3>
                    <p>The actual content that was sent to subscribers.</p>
                </div>

                <div style="padding: 1rem; border: 1px solid var(--color-border); border-radius: 0.5rem; background: var(--color-surface); max-height: 400px; overflow-y: auto;">
                    <?= $campaign['content'] ?>
                </div>
            </div>

            <?php if ($subscribers): ?>
                <div class="theme-card">
                    <div class="theme-card-head">
                        <h3>Target Subscribers</h3>
                        <p>Subscribers who will receive or have received this campaign.</p>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <strong>Total:</strong> <?= e((string) count($subscribers)) ?> subscribers
                    </div>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Audience</th>
                                    <th class="hide-mobile">Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($subscribers, 0, 10) as $subscriber): ?>
                                    <tr>
                                        <td>
                                            <a href="mailto:<?= e($subscriber['email']) ?>">
                                                <?= e($subscriber['email']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="pill pill-<?= match($subscriber['audience']) {
                                                'all' => 'info',
                                                'client' => 'primary',
                                                'tasker' => 'success',
                                                'partner' => 'warning',
                                                default => 'info'
                                            } ?>"><?= e($subscriber['audience_label'] ?? ucfirst($subscriber['audience'])) ?></span>
                                        </td>
                                        <td class="hide-mobile"><?= e($subscriber['source_route'] ?? 'Unknown') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($subscribers) > 10): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; font-style: italic;">
                                            ... and <?= e((string) (count($subscribers) - 10)) ?> more subscribers
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($deliveryReport): ?>
                <div class="theme-card">
                    <div class="theme-card-head">
                        <h3>Delivery Report</h3>
                        <p>Detailed breakdown of delivery statuses.</p>
                    </div>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deliveryReport as $report): ?>
                                    <tr>
                                        <td>
                                            <span class="pill pill-<?= match($report['status']) {
                                                'sent' => 'success',
                                                'delivered' => 'primary',
                                                'failed' => 'danger',
                                                'bounced' => 'warning',
                                                'pending' => 'info',
                                                default => 'info'
                                            } ?>"><?= e(ucfirst($report['status'])) ?></span>
                                        </td>
                                        <td><?= e((string) $report['count']) ?></td>
                                        <td>
                                            <?php if ($stats['total_subscribers'] > 0): ?>
                                                <?= e(number_format(($report['count'] / $stats['total_subscribers']) * 100, 1)) ?>%
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="theme-form-actions">
            <a href="<?= e(url_for('admin/newsletter-campaigns')) ?>" class="button button-secondary">Back to Campaigns</a>
            
            <?php if (in_array($campaign['status'], ['draft', 'failed'])): ?>
                <a href="<?= e(url_for('admin/newsletter-campaigns/edit', ['id' => $campaign['id']])) ?>" class="button">Edit Campaign</a>
            <?php endif; ?>

            <?php if ($campaign['status'] === 'draft'): ?>
                <form method="post" action="<?= e(url_for('admin/newsletter-campaigns/schedule')) ?>" style="display: inline;">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="campaign_id" value="<?= e((string) $campaign['id']) ?>">
                    <button type="submit" class="button" data-confirm="Schedule this campaign for sending?">Schedule Campaign</button>
                </form>
            <?php endif; ?>

            <?php if ($campaign['status'] === 'scheduled'): ?>
                <form method="post" action="<?= e(url_for('admin/newsletter-campaigns/send')) ?>" style="display: inline;">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="campaign_id" value="<?= e((string) $campaign['id']) ?>">
                    <button type="submit" class="button" data-confirm="Send this campaign now?">Send Now</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>
