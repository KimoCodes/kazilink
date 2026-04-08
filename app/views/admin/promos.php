<div class="container">
    <section class="panel">
        <?php
        $title = 'Promo Codes';
        $eyebrow = 'Admin';
        $intro = 'Create discounts, control redemption limits, and target specific users when needed.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="home-standards-grid">
            <article class="feature-card">
                <h3><?= isset($editingPromo['id']) ? 'Edit promo' : 'Create promo' ?></h3>
                <form method="post" action="<?= e(url_for('admin/save-promo')) ?>" class="stack-form">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="promo_id" value="<?= e((string) ($editingPromo['id'] ?? 0)) ?>">
                    <label><span>Code</span><input type="text" name="code" value="<?= e((string) ($editingPromo['code'] ?? '')) ?>" placeholder="WELCOME50"></label>
                    <label>
                        <span>Type</span>
                        <select name="type">
                            <option value="percent" <?= (($editingPromo['type'] ?? 'percent') === 'percent') ? 'selected' : '' ?>>Percent</option>
                            <option value="fixed_rwf" <?= (($editingPromo['type'] ?? '') === 'fixed_rwf') ? 'selected' : '' ?>>Fixed RWF</option>
                        </select>
                    </label>
                    <label><span>Amount</span><input type="number" min="1" name="amount" value="<?= e((string) ($editingPromo['amount'] ?? '')) ?>"></label>
                    <label><span>Max redemptions</span><input type="number" min="1" name="max_redemptions" value="<?= e((string) ($editingPromo['max_redemptions'] ?? '')) ?>" placeholder="Leave blank for unlimited"></label>
                    <label><span>Expires at</span><input type="datetime-local" name="expires_at" value="<?= e(isset($editingPromo['expires_at']) && $editingPromo['expires_at'] ? date('Y-m-d\TH:i', strtotime((string) $editingPromo['expires_at'])) : '') ?>"></label>
                    <label class="checkbox-row"><input type="checkbox" name="active" value="1" <?= !isset($editingPromo['active']) || (int) ($editingPromo['active'] ?? 0) === 1 ? 'checked' : '' ?>> <span>Promo is active</span></label>
                    <label>
                        <span>Target specific users</span>
                        <select name="target_user_ids[]" multiple size="6">
                            <?php foreach ($users as $user): ?>
                                <option value="<?= e((string) $user['id']) ?>" <?= in_array((int) $user['id'], $editingPromoUserIds ?? [], true) ? 'selected' : '' ?>>
                                    <?= e((string) ($user['full_name'] ?? $user['email'])) ?> (<?= e((string) $user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="button"><?= isset($editingPromo['id']) ? 'Save promo' : 'Create promo' ?></button>
                </form>
            </article>

            <article class="feature-card">
                <h3>Existing promo codes</h3>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Code</th><th>Discount</th><th>Targets</th><th>Redeemed</th><th>Expires</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($promos as $promo): ?>
                                <tr>
                                    <td><span class="text-mono"><?= e((string) $promo['code']) ?></span></td>
                                    <td><?= e((string) $promo['amount']) ?><?= (string) $promo['type'] === 'percent' ? '%' : ' RWF' ?></td>
                                    <td><?= e((string) $promo['targeted_user_count']) ?></td>
                                    <td><?= e((string) $promo['redemption_count']) ?></td>
                                    <td><?= !empty($promo['expires_at']) ? e(dateFmt((string) $promo['expires_at'])) : 'No expiry' ?></td>
                                    <td><?= (int) $promo['active'] === 1 ? 'Active' : 'Inactive' ?></td>
                                    <td><a class="button button-secondary button-small" href="<?= e(url_for('admin/promos', ['id' => (int) $promo['id']])) ?>">Edit</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
</div>
