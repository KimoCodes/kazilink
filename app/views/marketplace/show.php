<?php
$bids = is_array($bids ?? null) ? $bids : [];
$selectedBid = is_array($selectedBid ?? null) ? $selectedBid : null;
$canManage = (bool) ($canManage ?? false);
$isSelectedBuyer = (bool) ($isSelectedBuyer ?? false);
?>
<div class="container">
    <section class="task-detail-layout">
        <article class="panel detail-body">
            <?php
            $title = (string) $listing['title'];
            $eyebrow = 'Marketplace Workspace';
            $intro = $canManage
                ? 'Review bids, select the highest offer, and unlock contact information when you confirm the sale.'
                : 'The seller selected your bid. Contact information is now visible to both sides.';
            $primaryAction = ['label' => 'Back to marketplace', 'href' => url_for('marketplace/index')];
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <div class="summary-grid">
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Starting price</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e(moneyRwf($listing['starting_price'])) ?></strong>
                        <span>Original listing amount</span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Status</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e(ucfirst((string) $listing['status'])) ?></strong>
                        <span><?= $selectedBid !== null ? 'Buyer selected and contacts unlocked' : 'Still collecting bids' ?></span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Location</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $listing['city']) ?></strong>
                        <span><?= e((string) $listing['country']) ?></span>
                    </div>
                </article>
            </div>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Description</h2>
                        <p class="section-intro">Keep the listing details visible while you review offers or prepare direct follow-up.</p>
                    </div>
                </div>
                <div class="detail-copy"><?= nl2br(e((string) $listing['description'])) ?></div>
            </section>

            <?php if ($selectedBid !== null): ?>
                <section class="booking-section">
                    <div class="section-head">
                        <div>
                            <h2>Shared contact information</h2>
                            <p class="section-intro">The selected buyer and the product owner can now reach each other directly.</p>
                        </div>
                    </div>

                    <div class="summary-grid">
                        <article class="sidebar-card">
                            <span class="sidebar-item-label">Seller</span>
                            <strong><?= e((string) $selectedBid['seller_name']) ?></strong>
                            <p class="muted"><?= e((string) $selectedBid['seller_email']) ?></p>
                            <p class="muted"><?= !empty($selectedBid['seller_phone']) ? e((string) $selectedBid['seller_phone']) : 'Phone not added yet' ?></p>
                        </article>
                        <article class="sidebar-card">
                            <span class="sidebar-item-label">Buyer</span>
                            <strong><?= e((string) $selectedBid['buyer_name']) ?></strong>
                            <p class="muted"><?= e((string) $selectedBid['buyer_email']) ?></p>
                            <p class="muted"><?= !empty($selectedBid['buyer_phone']) ? e((string) $selectedBid['buyer_phone']) : 'Phone not added yet' ?></p>
                        </article>
                        <article class="sidebar-card">
                            <span class="sidebar-item-label">Selected bid</span>
                            <strong><?= e(moneyRwf($selectedBid['amount'])) ?></strong>
                            <p class="muted">Chosen at <?= e(dateFmt((string) $selectedBid['updated_at'])) ?></p>
                        </article>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <section class="booking-section">
                    <div class="section-head">
                        <div>
                            <h2>Incoming bids</h2>
                            <p class="section-intro">The product owner can only select the highest current bid for this listing.</p>
                        </div>
                        <span class="pill pill-info"><?= count($bids) ?> bid<?= count($bids) === 1 ? '' : 's' ?></span>
                    </div>

                    <?php if ($bids === []): ?>
                        <?php
                        $emptyIcon = '💸';
                        $emptyTitle = 'No bids yet';
                        $emptyMessage = 'Buyers will appear here as soon as they place their offers.';
                        require BASE_PATH . '/app/views/partials/empty_state.php';
                        ?>
                    <?php else: ?>
                        <?php
                        $selectableHighestBidId = 0;
                        $highestPendingBidAmount = null;

                        foreach ($bids as $candidateBid) {
                            if ((string) ($candidateBid['status'] ?? '') !== 'pending') {
                                continue;
                            }

                            $selectableHighestBidId = (int) ($candidateBid['id'] ?? 0);
                            $highestPendingBidAmount = (float) ($candidateBid['amount'] ?? 0);
                            break;
                        }
                        ?>
                        <div class="card-list">
                            <?php foreach ($bids as $bid): ?>
                                <?php
                                $isHighestBid = (int) ($bid['id'] ?? 0) === $selectableHighestBidId;
                                $isTiedHighestPendingBid = !$isHighestBid
                                    && $highestPendingBidAmount !== null
                                    && (string) ($bid['status'] ?? '') === 'pending'
                                    && (float) ($bid['amount'] ?? 0) === $highestPendingBidAmount;
                                ?>
                                <article class="bid-card task-bid-card">
                                    <div class="card-header">
                                        <div>
                                            <h3><?= e((string) $bid['buyer_name']) ?></h3>
                                            <p class="inline-meta">
                                                <span><?= e((string) $bid['buyer_email']) ?></span>
                                                <span>•</span>
                                                <span><?= e(dateFmt((string) $bid['created_at'])) ?></span>
                                            </p>
                                        </div>
                                        <div class="button-group task-bid-badges">
                                            <?php if ($isHighestBid): ?>
                                                <span class="pill pill-success">Highest bid</span>
                                            <?php elseif ($isTiedHighestPendingBid): ?>
                                                <span class="pill pill-info">Tied highest</span>
                                            <?php endif; ?>
                                            <?php $status = (string) $bid['status']; $label = ucfirst((string) $bid['status']); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                                        </div>
                                    </div>

                                    <div class="task-bid-message">
                                        <strong class="price"><?= e(moneyRwf($bid['amount'])) ?></strong>
                                        <?php if (!empty($bid['message'])): ?>
                                            <p><?= nl2br(e((string) $bid['message'])) ?></p>
                                        <?php else: ?>
                                            <p class="text-muted">No message included with this bid.</p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="task-bid-footer">
                                        <span class="text-muted"><?= !empty($bid['buyer_phone']) ? e((string) $bid['buyer_phone']) : 'Phone shared after selection' ?></span>
                                        <?php if ((string) $listing['status'] === 'open' && (string) $bid['status'] === 'pending' && $isHighestBid): ?>
                                            <form method="post" action="<?= e(url_for('marketplace/select-bid')) ?>">
                                                <?= Csrf::input() ?>
                                                <input type="hidden" name="bid_id" value="<?= e((string) $bid['id']) ?>">
                                                <button type="submit" class="button button-small" data-confirm="Select this highest bid and share contact information?">Select highest bid</button>
                                            </form>
                                        <?php elseif ($isTiedHighestPendingBid): ?>
                                            <span class="text-muted">Top amount tied. Earliest matching bid is selectable first.</span>
                                        <?php elseif ((string) $bid['status'] === 'selected'): ?>
                                            <span class="pill pill-success">Buyer selected</span>
                                        <?php else: ?>
                                            <span class="text-muted">No action available</span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </article>

        <aside class="sidebar-stack">
            <div class="sidebar-card">
                <span class="sidebar-item-label">Seller</span>
                <strong><?= e((string) $listing['seller_name']) ?></strong>
                <p class="muted"><?= e((string) $listing['city']) ?><?= !empty($listing['region']) ? ', ' . e((string) $listing['region']) : '' ?>, <?= e((string) $listing['country']) ?></p>
            </div>
        </aside>
    </section>
</div>
