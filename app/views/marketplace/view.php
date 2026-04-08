<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
$bidAmountValue = !empty($errors) ? old_value('marketplace_bid_amount') : '';
$bidMessageValue = !empty($errors) ? old_value('marketplace_bid_message') : '';
?>
<div class="container">
    <section class="task-detail-layout">
        <article class="panel detail-body">
            <?php
            $title = (string) $listing['title'];
            $eyebrow = 'Marketplace Listing';
            $intro = 'Review the item details and place one clear bid if you want to buy it.';
            $primaryAction = ['label' => 'Back to marketplace', 'href' => url_for('marketplace/index')];
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <div class="summary-grid">
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Starting price</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e(moneyRwf($listing['starting_price'])) ?></strong>
                        <span>Minimum amount for valid bids</span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Location</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $listing['city']) ?></strong>
                        <span><?= e((string) $listing['country']) ?></span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Seller</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $listing['seller_name']) ?></strong>
                        <span>Contact details appear after the highest bid is selected</span>
                    </div>
                </article>
            </div>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Description</h2>
                        <p class="section-intro">Use the full description to judge value, condition, and whether your bid makes sense.</p>
                    </div>
                </div>
                <div class="detail-copy"><?= nl2br(e((string) $listing['description'])) ?></div>
            </section>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Your bid</h2>
                        <p class="section-intro">You can place one bid per listing. The seller can only select the highest bid.</p>
                    </div>
                </div>

                <?php if ($existingBid !== null): ?>
                    <article class="bid-card task-bid-card">
                        <div class="card-header">
                            <div>
                                <h3>Your offer</h3>
                                <p class="inline-meta">
                                    <span><?= e(dateFmt((string) $existingBid['created_at'])) ?></span>
                                </p>
                            </div>
                            <?php $status = (string) $existingBid['status']; $label = ucfirst((string) $existingBid['status']); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                        </div>
                        <div class="task-bid-message">
                            <strong class="price"><?= e(moneyRwf($existingBid['amount'])) ?></strong>
                            <?php if (!empty($existingBid['message'])): ?>
                                <p><?= nl2br(e((string) $existingBid['message'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php else: ?>
                    <form method="post" action="<?= e(url_for('marketplace/place-bid')) ?>" class="form-grid" novalidate>
                        <?= Csrf::input() ?>
                        <input type="hidden" name="listing_id" value="<?= e((string) $listing['id']) ?>">

                        <?php
                        $name = 'amount';
                        $label = 'Bid amount (RWF)';
                        $type = 'number';
                        $value = $bidAmountValue;
                        $placeholder = (string) ((int) $listing['starting_price']);
                        $required = true;
                        $hint = 'Your bid must be at least the starting price.';
                        $error = field_error($fieldErrors, 'amount');
                        $attrs = ['step' => '1000', 'min' => (string) max(1000, (int) $listing['starting_price'])];
                        require BASE_PATH . '/app/views/partials/form_field.php';
                        ?>

                        <?php
                        $name = 'message';
                        $label = 'Message';
                        $as = 'textarea';
                        $value = $bidMessageValue;
                        $placeholder = 'Add pickup timing, offline payment timing, or any important note for the seller.';
                        $required = false;
                        $hint = 'A short clear message can help your bid stand out.';
                        $error = field_error($fieldErrors, 'message');
                        $attrs = ['maxlength' => '2000', 'rows' => '5'];
                        require BASE_PATH . '/app/views/partials/form_field.php';
                        ?>

                        <button type="submit" class="button">Submit bid</button>
                    </form>
                <?php endif; ?>
            </section>
        </article>

        <aside class="sidebar-stack">
            <div class="sidebar-card">
                <span class="sidebar-item-label">Listing details</span>
                <div class="sidebar-list">
                    <div>
                        <span class="sidebar-item-label">Status</span>
                        <div class="sidebar-item-value">Open for bids</div>
                    </div>
                    <div>
                        <span class="sidebar-item-label">Region</span>
                        <div class="sidebar-item-value"><?= !empty($listing['region']) ? e((string) $listing['region']) : 'Not specified' ?></div>
                    </div>
                    <div>
                        <span class="sidebar-item-label">Posted</span>
                        <div class="sidebar-item-value"><?= e(dateFmt((string) $listing['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </aside>
    </section>
</div>
