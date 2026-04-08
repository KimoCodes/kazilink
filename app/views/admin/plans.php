<div class="container">
    <section class="panel">
        <?php
        $title = 'Subscription Plans';
        $eyebrow = 'Admin';
        $intro = 'Manage monthly subscription pricing, visibility levels, and plan availability.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="home-standards-grid">
            <article class="feature-card">
                <h3><?= isset($editingPlan['id']) ? 'Edit plan' : 'Create plan' ?></h3>
                <form method="post" action="<?= e(url_for('admin/save-plan')) ?>" class="stack-form">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="plan_id" value="<?= e((string) ($editingPlan['id'] ?? 0)) ?>">
                    <label><span>Slug</span><input type="text" name="slug" value="<?= e((string) ($editingPlan['slug'] ?? '')) ?>" placeholder="basic"></label>
                    <label><span>Name</span><input type="text" name="name" value="<?= e((string) ($editingPlan['name'] ?? '')) ?>" placeholder="Basic"></label>
                    <label><span>Price (RWF)</span><input type="number" min="0" name="price_rwf" value="<?= e((string) ($editingPlan['price_rwf'] ?? '')) ?>"></label>
                    <label><span>Visibility level</span><input type="number" min="1" max="3" name="visibility_level" value="<?= e((string) ($editingPlan['visibility_level'] ?? 1)) ?>"></label>
                    <label><span>Applications per day</span><input type="number" min="1" name="max_applications_per_day" value="<?= e((string) ($editingPlan['max_applications_per_day'] ?? 5)) ?>"></label>
                    <label><span>Priority level</span><input type="number" min="1" max="3" name="priority_level" value="<?= e((string) ($editingPlan['priority_level'] ?? 1)) ?>"></label>
                    <label><span>Job alert delay (minutes)</span><input type="number" min="-60" max="1440" name="job_alert_delay_minutes" value="<?= e((string) ($editingPlan['job_alert_delay_minutes'] ?? 0)) ?>"></label>
                    <label><span>Max active jobs</span><input type="number" min="1" name="max_active_jobs" value="<?= e((string) ($editingPlan['max_active_jobs'] ?? 1)) ?>"></label>
                    <label><span>Commission discount (%)</span><input type="number" min="0" max="100" step="0.01" name="commission_discount" value="<?= e((string) ($editingPlan['commission_discount'] ?? 0)) ?>"></label>
                    <label><span>Badge name</span><input type="text" name="badge_name" value="<?= e((string) ($editingPlan['badge_name'] ?? '')) ?>" placeholder="Verified Pro"></label>
                    <label class="checkbox-row"><input type="checkbox" name="active" value="1" <?= !isset($editingPlan['active']) || (int) ($editingPlan['active'] ?? 0) === 1 ? 'checked' : '' ?>> <span>Plan is active</span></label>
                    <button type="submit" class="button"><?= isset($editingPlan['id']) ? 'Save plan' : 'Create plan' ?></button>
                </form>
            </article>

            <article class="feature-card">
                <h3>Current plans</h3>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Name</th><th>Slug</th><th>Price</th><th>Priority</th><th>Applications</th><th>Jobs</th><th>Badge</th><th>Status</th><th>Subscribers</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $plan): ?>
                                <tr>
                                    <td><strong><?= e((string) $plan['name']) ?></strong></td>
                                    <td><span class="text-mono"><?= e((string) $plan['slug']) ?></span></td>
                                    <td><?= e(moneyRwf((int) $plan['price_rwf'])) ?></td>
                                    <td><?= e((string) $plan['priority_level']) ?></td>
                                    <td><?= e((string) $plan['max_applications_per_day']) ?>/day</td>
                                    <td><?= e((string) $plan['max_active_jobs']) ?></td>
                                    <td><?= !empty($plan['badge_name']) ? e((string) $plan['badge_name']) : '—' ?></td>
                                    <td><?= (int) $plan['active'] === 1 ? 'Active' : 'Inactive' ?></td>
                                    <td><?= e((string) $plan['subscription_count']) ?></td>
                                    <td><a class="button button-secondary button-small" href="<?= e(url_for('admin/plans', ['id' => (int) $plan['id']])) ?>">Edit</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
</div>
