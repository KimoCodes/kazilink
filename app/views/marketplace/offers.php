<?php
$offers = is_array($offers ?? null) ? $offers : [];
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'My Offers';
        $eyebrow = 'Marketplace';
        $intro = 'Review every bid you have placed and jump into any listing where your offer was selected.';
        $primaryAction = ['label' => 'Browse listings', 'href' => url_for('marketplace/index')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <?php if ($offers === []): ?>
            <?php
            $emptyIcon = '💸';
            $emptyTitle = 'No offers placed yet';
            $emptyMessage = 'Browse the marketplace and place your first bid on an item you want.';
            $emptyAction = ['label' => 'Browse marketplace', 'href' => url_for('marketplace/index'), 'class' => 'button'];
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="card-list">
                <?php foreach ($offers as $offer): ?>
                    <?php
                    $offerRoute = (string) $offer['status'] === 'selected' ? 'marketplace/show' : 'marketplace/view';
                    $offerLabel = (string) $offer['status'] === 'selected' ? 'Open contact details' : 'Open listing';
                    ?>
                    <article class="task-card">
                        <div class="task-card-header">
                            <div class="task-card-title-wrap">
                                <h3 class="task-card-title task-card-title-compact">
                                    <a href="<?= e(url_for($offerRoute, ['id' => (int) $offer['listing_id']])) ?>">
                                        <?= e((string) $offer['title']) ?>
                                    </a>
                                </h3>
                                <div class="task-card-meta">
                                    <span>Seller: <?= e((string) $offer['seller_name']) ?></span>
                                    <span>•</span>
                                    <span><?= e((string) $offer['city']) ?>, <?= e((string) $offer['country']) ?></span>
                                </div>
                            </div>
                            <?php $status = (string) $offer['status']; $label = ucfirst((string) $offer['status']); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                        </div>
                        <div class="task-card-footer">
                            <div class="task-card-footer-copy">
                                <div class="price task-card-price"><?= e(moneyRwf($offer['amount'])) ?></div>
                                <span class="muted task-card-meta-note">Listing status: <?= e(ucfirst((string) $offer['listing_status'])) ?></span>
                            </div>
                            <a class="button button-secondary button-small" href="<?= e(url_for($offerRoute, ['id' => (int) $offer['listing_id']])) ?>">
                                <?= e($offerLabel) ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
