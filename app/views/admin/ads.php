<?php
$ads = is_array($ads ?? null) ? $ads : [];
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$editingAd = is_array($editingAd ?? null) ? $editingAd : null;
$isEditing = $editingAd !== null && isset($editingAd['id']);
$editingAdMediaUrls = !empty($editingAd['media_path']) ? public_url_candidates((string) $editingAd['media_path']) : [];
$editingAdMediaUrl = $editingAdMediaUrls[0] ?? null;
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'Manage Ads';
        $eyebrow = 'Admin';
        $intro = 'Create, update, and activate promotional ads for the home page and marketplace discovery page.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="task-detail-layout">
            <article class="panel panel-subtle">
                <div class="section-head">
                    <div>
                        <h2><?= $isEditing ? 'Edit ad' : 'Create ad' ?></h2>
                        <p class="section-intro">Ads are admin-managed only and appear in the placements you choose below.</p>
                    </div>
                </div>

                <form method="post" action="<?= e(url_for('admin/save-ad')) ?>" class="form-grid" enctype="multipart/form-data" novalidate>
                    <?= Csrf::input() ?>
                    <input type="hidden" name="ad_id" value="<?= e((string) ($editingAd['id'] ?? '')) ?>">

                    <?php
                    $name = 'title';
                    $label = 'Title';
                    $value = (string) ($editingAd['title'] ?? '');
                    $placeholder = 'Promote something valuable';
                    $required = true;
                    $hint = 'Short and strong works best.';
                    $error = field_error($fieldErrors, 'title');
                    require BASE_PATH . '/app/views/partials/form_field.php';
                    ?>

                    <?php
                    $name = 'body';
                    $label = 'Body';
                    $as = 'textarea';
                    $value = (string) ($editingAd['body'] ?? '');
                    $placeholder = 'Explain the offer, event, promotion, or announcement clearly.';
                    $required = true;
                    $hint = 'Keep it concise and action-oriented.';
                    $error = field_error($fieldErrors, 'body');
                    $attrs = ['rows' => '5', 'maxlength' => '1000'];
                    require BASE_PATH . '/app/views/partials/form_field.php';
                    ?>

                    <div class="form-row">
                        <label for="media">Media upload</label>
                        <input id="media" name="media" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm">
                        <?php if (!empty($editingAd['media_path'])): ?>
                            <div class="field-hint" style="display:block; margin-top: var(--space-2);">
                                Current media:
                                <?php if ($editingAdMediaUrl !== null): ?>
                                    <a href="<?= e($editingAdMediaUrl) ?>" target="_blank" rel="noreferrer"><?= e((string) $editingAd['media_path']) ?></a>
                                <?php else: ?>
                                    <?= e((string) $editingAd['media_path']) ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="field-hint">Optional. Upload JPG, PNG, WebP, MP4, or WebM up to 15MB.</span>
                        <?php endif; ?>
                        <?php if (field_error($fieldErrors, 'media')): ?>
                            <span class="form-error"><?= e((string) field_error($fieldErrors, 'media')) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="filter-grid task-filter-grid">
                        <?php
                        $name = 'cta_label';
                        $label = 'CTA label';
                        $as = 'input';
                        $type = 'text';
                        $value = (string) ($editingAd['cta_label'] ?? '');
                        $placeholder = 'Learn more';
                        $required = false;
                        $hint = null;
                        $error = field_error($fieldErrors, 'cta_label');
                        $attrs = [];
                        require BASE_PATH . '/app/views/partials/form_field.php';
                        ?>

                        <?php
                        $name = 'cta_url';
                        $label = 'CTA URL';
                        $as = 'input';
                        $type = 'text';
                        $value = (string) ($editingAd['cta_url'] ?? '');
                        $placeholder = '/?route=marketplace/index or https://example.com';
                        $required = false;
                        $hint = 'Use a local path or full URL.';
                        $error = field_error($fieldErrors, 'cta_url');
                        $attrs = [];
                        require BASE_PATH . '/app/views/partials/form_field.php';
                        ?>

                        <?php
                        $name = 'placement';
                        $label = 'Placement';
                        $as = 'select';
                        $value = (string) ($editingAd['placement'] ?? 'home');
                        $required = true;
                        $hint = null;
                        $error = field_error($fieldErrors, 'placement');
                        $options = [
                            ['value' => 'home', 'label' => 'Home page'],
                            ['value' => 'marketplace', 'label' => 'Marketplace'],
                        ];
                        require BASE_PATH . '/app/views/partials/form_field.php';
                        ?>

                        <?php
                        $name = 'sort_order';
                        $label = 'Sort order';
                        $as = 'input';
                        $type = 'number';
                        $value = (string) ($editingAd['sort_order'] ?? '0');
                        $placeholder = '0';
                        $required = true;
                        $hint = 'Lower numbers appear first, and each placement should use a unique order.';
                        $error = field_error($fieldErrors, 'sort_order');
                        $attrs = ['step' => '1'];
                        require BASE_PATH . '/app/views/partials/form_field.php';
                        ?>
                    </div>

                    <div class="form-row">
                        <label for="is_active">Status</label>
                        <select id="is_active" name="is_active">
                            <option value="1" <?= (string) ($editingAd['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= (string) ($editingAd['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="hero-actions">
                        <button type="submit" class="button"><?= $isEditing ? 'Update ad' : 'Create ad' ?></button>
                        <?php if ($isEditing): ?>
                            <a class="button button-secondary" href="<?= e(url_for('admin/ads')) ?>">Cancel edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </article>

            <aside class="sidebar-stack">
                <div class="sidebar-card">
                    <span class="sidebar-item-label">Existing ads</span>
                    <div class="sidebar-list">
                        <div>
                            <span class="sidebar-item-label">Total</span>
                            <div class="sidebar-item-value"><?= e((string) count($ads)) ?></div>
                        </div>
                        <div>
                            <span class="sidebar-item-label">Active</span>
                            <div class="sidebar-item-value"><?= e((string) count(array_filter($ads, static fn (array $ad): bool => (int) $ad['is_active'] === 1))) ?></div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <h2>Published Ads</h2>
                <p class="section-intro">Review each ad, edit its content, or toggle whether it is live.</p>
            </div>
        </div>

        <?php if ($ads === []): ?>
            <?php
            $emptyIcon = '📣';
            $emptyTitle = 'No ads yet';
            $emptyMessage = 'Create your first ad above to publish it on the home page or marketplace.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Placement</th>
                            <th>Media</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ads as $ad): ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) $ad['title']) ?></strong><br>
                                    <span class="text-muted"><?= e(mb_strlen((string) $ad['body']) > 90 ? mb_substr((string) $ad['body'], 0, 90) . '…' : (string) $ad['body']) ?></span>
                                </td>
                                <td><?= e(ucfirst((string) $ad['placement'])) ?></td>
                                <td><?= !empty($ad['media_type']) ? e(ucfirst((string) $ad['media_type'])) : 'Text only' ?></td>
                                <td><?= e((string) $ad['sort_order']) ?></td>
                                <td>
                                    <?php $status = (int) $ad['is_active'] === 1 ? 'active' : 'inactive'; $label = (int) $ad['is_active'] === 1 ? 'Active' : 'Inactive'; require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                                </td>
                                <td>
                                    <div class="button-group">
                                        <a class="button button-secondary button-small" href="<?= e(url_for('admin/ads', ['id' => (int) $ad['id']])) ?>">Edit</a>
                                        <form method="post" action="<?= e(url_for('admin/toggle-ad')) ?>">
                                            <?= Csrf::input() ?>
                                            <input type="hidden" name="ad_id" value="<?= e((string) $ad['id']) ?>">
                                            <button type="submit" class="button button-secondary button-small"><?= (int) $ad['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
