<?php
$listings = is_array($listings ?? null) ? $listings : [];
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'My Listings';
        $eyebrow = 'Marketplace';
        $intro = 'Track what you are selling, how many bids have arrived, and which listing has already been sold.';
        $primaryAction = ['label' => 'Sell another item', 'href' => url_for('marketplace/create')];
        $secondaryAction = ['label' => 'Browse marketplace', 'href' => url_for('marketplace/index')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <?php if ($listings === []): ?>
            <?php
            $emptyIcon = '🛍️';
            $emptyTitle = 'No listings posted yet';
            $emptyMessage = 'Create your first marketplace listing to start collecting bids.';
            $emptyAction = ['label' => 'Sell an item', 'href' => url_for('marketplace/create'), 'class' => 'button'];
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="card-list">
                <?php foreach ($listings as $listing): ?>
                    <article class="task-card">
                        <div class="task-card-header">
                            <div class="task-card-title-wrap">
                                <h3 class="task-card-title task-card-title-compact">
                                    <a href="<?= e(url_for('marketplace/show', ['id' => (int) $listing['id']])) ?>"><?= e((string) $listing['title']) ?></a>
                                </h3>
                                <div class="task-card-meta">
                                    <span><?= e((string) $listing['bid_count']) ?> bid<?= (int) $listing['bid_count'] === 1 ? '' : 's' ?></span>
                                    <span>•</span>
                                    <span>Highest: <?= e(moneyRwf($listing['highest_bid'])) ?></span>
                                </div>
                            </div>
                            <?php $status = (string) $listing['status']; $label = ucfirst((string) $listing['status']); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                        </div>
                        <p class="task-card-excerpt"><?= e(mb_strlen((string) $listing['description']) > 150 ? mb_substr((string) $listing['description'], 0, 150) . '…' : (string) $listing['description']) ?></p>
                        <div class="task-card-footer">
                            <div class="task-card-footer-copy">
                                <div class="price task-card-price"><?= e(moneyRwf($listing['starting_price'])) ?></div>
                                <span class="muted task-card-meta-note"><?= e((string) $listing['city']) ?>, <?= e((string) $listing['country']) ?></span>
                            </div>
                            <a class="button button-secondary button-small" href="<?= e(url_for('marketplace/show', ['id' => (int) $listing['id']])) ?>">Open listing</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
