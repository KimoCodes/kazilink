<div class="container">
    <section class="panel">
        <?php
        $title = 'Subscription Settings';
        $eyebrow = 'Admin';
        $intro = 'Manage operational subscription settings from the database instead of environment variables alone.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="home-standards-grid">
            <article class="feature-card">
                <h3>Grace period</h3>
                <form method="post" action="<?= e(url_for('admin/save-settings')) ?>" class="stack-form">
                    <?= Csrf::input() ?>
                    <label>
                        <span>Subscription grace period (days)</span>
                        <input type="number" min="0" max="7" name="subscription_grace_days" value="<?= e((string) $graceDays) ?>">
                    </label>
                    <p class="muted">This controls how long a paid subscriber keeps access after the billing period ends before hard restrictions apply.</p>
                    <button type="submit" class="button">Save settings</button>
                </form>
            </article>

            <article class="feature-card">
                <h3>Stored settings</h3>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Key</th><th>Value</th><th>Updated</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($settings as $setting): ?>
                                <tr>
                                    <td><span class="text-mono"><?= e((string) $setting['setting_key']) ?></span></td>
                                    <td><?= e((string) $setting['setting_value']) ?></td>
                                    <td><?= e(dateFmt((string) $setting['updated_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
</div>
