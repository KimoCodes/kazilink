<div class="container">
    <section class="panel">
        <?php
        $title = 'Notifications';
        $eyebrow = 'Inbox';
        $intro = 'Review payment alerts and mark them as read after taking action.';
        $secondaryLink = ['label' => 'Back', 'href' => url_for('home/index')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        $notifications = is_array($notifications ?? null) ? $notifications : [];
        ?>

        <?php if ($notifications === []): ?>
            <?php
            $emptyTitle = 'No notifications yet';
            $emptyMessage = 'When payment events require your attention, they will appear here.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="stack-form">
                <?php foreach ($notifications as $notification): ?>
                    <article class="info-card">
                        <div class="task-summary-metric-row">
                            <strong><?= e((string) $notification['title']) ?></strong>
                            <span><?= e(dateFmt((string) $notification['created_at'])) ?></span>
                        </div>
                        <p><?= e((string) $notification['body']) ?></p>
                        <div class="task-summary-metric-row">
                            <?php if (!empty($notification['link_url'])): ?>
                                <a class="button button-secondary button-small" href="<?= e((string) $notification['link_url']) ?>">Open</a>
                            <?php endif; ?>
                            <?php if ((string) $notification['status'] === 'unread'): ?>
                                <form method="post" action="<?= e(url_for('notifications/mark-read')) ?>">
                                    <?= Csrf::input() ?>
                                    <input type="hidden" name="notification_id" value="<?= e((string) $notification['id']) ?>">
                                    <button type="submit" class="button button-small">Mark read</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Read</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
