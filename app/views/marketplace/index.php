<?php
$filters = is_array($filters ?? null) ? $filters : [];
$listings = is_array($listings ?? null) ? $listings : [];
$ads = is_array($ads ?? null) ? $ads : [];
$pagination = is_array($pagination ?? null) ? $pagination : ['total' => count($listings), 'page' => 1, 'total_pages' => 1];
?>
<div class="container">
    <section class="panel panel-subtle filter-panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Marketplace</span>
                <h1>Browse items for sale</h1>
                <p class="page-intro">Discover listings, compare current highest bids, and place an offer on the items you want.</p>
            </div>
            <div class="page-actions">
                <a class="button button-secondary" href="<?= e(url_for('marketplace/my-listings')) ?>">My listings</a>
                <a class="button button-secondary" href="<?= e(url_for('marketplace/offers')) ?>">My offers</a>
                <a class="button" href="<?= e(url_for('marketplace/create')) ?>">Sell an item</a>
            </div>
        </div>

        <form method="get" action="<?= e(url_for('marketplace/index')) ?>" class="form-grid" novalidate>
            <input type="hidden" name="route" value="marketplace/index">
            <div class="filter-grid task-filter-grid">
                <div class="form-row">
                    <label for="q">Keyword</label>
                    <input id="q" name="q" type="text" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="Phone, chair, fridge">
                </div>
                <div class="form-row">
                    <label for="city">City</label>
                    <input id="city" name="city" type="text" value="<?= e((string) ($filters['city'] ?? '')) ?>" placeholder="Kigali">
                </div>
                <div class="form-row">
                    <label for="min_price">Min price</label>
                    <input id="min_price" name="min_price" type="number" min="0" step="1000" value="<?= e((string) ($filters['min_price'] ?? '')) ?>" placeholder="10000">
                </div>
                <div class="form-row">
                    <label for="max_price">Max price</label>
                    <input id="max_price" name="max_price" type="number" min="0" step="1000" value="<?= e((string) ($filters['max_price'] ?? '')) ?>" placeholder="500000">
                </div>
                <div class="form-row">
                    <label for="sort">Sort by</label>
                    <select id="sort" name="sort">
                        <option value="newest" <?= ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest first</option>
                        <option value="oldest" <?= ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                        <option value="price_high" <?= ($filters['sort'] ?? '') === 'price_high' ? 'selected' : '' ?>>Highest bid</option>
                        <option value="price_low" <?= ($filters['sort'] ?? '') === 'price_low' ? 'selected' : '' ?>>Lowest price</option>
                        <option value="most_bids" <?= ($filters['sort'] ?? '') === 'most_bids' ? 'selected' : '' ?>>Most bids</option>
                    </select>
                </div>
            </div>
            <div class="hero-actions task-filter-actions">
                <button type="submit" class="button">Search listings</button>
                <a class="button button-secondary" href="<?= e(url_for('marketplace/index')) ?>">Clear filters</a>
            </div>
        </form>
    </section>

    <?php
    if ($ads !== []) {
        require BASE_PATH . '/app/views/partials/ad-banner.php';
    }
    ?>

    <section class="panel">
        <div class="task-results-toolbar">
            <div>
                <h2 class="task-results-title">Open listings</h2>
                <p class="section-intro task-results-intro">Showing <?= count($listings) ?> of <?= e((string) ($pagination['total'] ?? count($listings))) ?> listing<?= (int) ($pagination['total'] ?? count($listings)) === 1 ? '' : 's' ?></p>
            </div>
            <span class="pill"><?= e((string) ($pagination['total'] ?? count($listings))) ?> Result<?= (int) ($pagination['total'] ?? count($listings)) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($listings === []): ?>
            <?php
            $emptyIcon = '🛍️';
            $emptyTitle = 'No listings match that search';
            $emptyMessage = 'Try a broader keyword, widen the price range, or check back when more items are posted.';
            $emptyAction = ['label' => 'See all listings', 'href' => url_for('marketplace/index')];
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="card-list">
                <?php foreach ($listings as $listing): ?>
                    <article class="task-card">
                        <div class="task-card-header">
                            <div class="task-card-title-wrap">
                                <h3 class="task-card-title task-card-title-compact">
                                    <a href="<?= e(url_for('marketplace/view', ['id' => (int) $listing['id']])) ?>"><?= e((string) $listing['title']) ?></a>
                                </h3>
                                <div class="task-card-meta">
                                    <span>📍 <?= e((string) $listing['city']) ?>, <?= e((string) $listing['country']) ?></span>
                                    <span>•</span>
                                    <span><?= e((string) $listing['bid_count']) ?> bid<?= (int) $listing['bid_count'] === 1 ? '' : 's' ?></span>
                                    <span>•</span>
                                    <span><?= e((string) $listing['seller_plan_name']) ?> visibility</span>
                                </div>
                            </div>
                            <?php $status = 'open'; $label = 'Open'; require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                        </div>
                        <p class="task-card-excerpt"><?= e(mb_strlen((string) $listing['description']) > 160 ? mb_substr((string) $listing['description'], 0, 160) . '…' : (string) $listing['description']) ?></p>
                        <div class="task-card-footer">
                            <div class="task-card-footer-copy">
                                <div class="price task-card-price"><?= e(moneyRwf($listing['highest_bid'])) ?></div>
                                <span class="muted task-card-meta-note">Seller: <strong><?= e((string) $listing['seller_name']) ?></strong></span>
                            </div>
                            <a class="button button-secondary button-small" href="<?= e(url_for('marketplace/view', ['id' => (int) $listing['id']])) ?>">View &amp; bid</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php
            $paginationRoute = 'marketplace/index';
            $paginationParams = array_filter($filters, static fn (mixed $value): bool => $value !== '');
            require BASE_PATH . '/app/views/partials/pagination.php';
            ?>
        <?php endif; ?>
    </section>
</div>
