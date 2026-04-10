<div class="container">
    <section class="panel">
        <?php
        $title = 'Website Theme Manager';
        $eyebrow = 'Admin';
        $intro = 'Manage colors, typography, spacing rhythm, and page-specific background images from the database.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <?php if (!$siteSettingsReady): ?>
            <div class="theme-alert theme-alert-error">
                The <span class="text-mono">site_settings</span> table is missing. Run the site settings migration before saving theme changes.
            </div>
        <?php endif; ?>

        <div class="theme-admin-grid">
            <article class="theme-card">
                <div class="theme-card-head">
                    <h2>Upload Background</h2>
                    <p>Assign a background image to the login page, home page, dashboard, or a custom route key.</p>
                </div>

                <form action="<?= e(url_for('admin/upload-theme-background')) ?>" method="post" enctype="multipart/form-data" class="theme-form" novalidate>
                    <?= Csrf::input() ?>

                    <label class="theme-field">
                        <span>Page</span>
                        <select name="page_target" id="page_target" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                            <option value="login">Login</option>
                            <option value="home">Home</option>
                            <option value="dashboard">Dashboard</option>
                            <option value="other">Other page</option>
                        </select>
                    </label>

                    <label class="theme-field" id="custom_page_wrap" hidden>
                        <span>Custom page key</span>
                        <input type="text" name="custom_page" placeholder="Example: marketing_about" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                        <small>Custom routes are stored as keys like <span class="text-mono">bg_marketing_about</span>.</small>
                    </label>

                    <label class="theme-field">
                        <span>Background image</span>
                        <input type="file" name="background_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                        <small>Accepted formats: JPG, PNG, WEBP. Maximum size: 5MB.</small>
                    </label>

                    <button type="submit" class="button" <?= !$siteSettingsReady ? 'disabled' : '' ?>>Upload background</button>
                </form>
            </article>

            <article class="theme-card">
                <div class="theme-card-head">
                    <h2>Theme Settings</h2>
                    <p>Set the calm neutral palette, typography, mode, and spacing scale without changing code.</p>
                </div>

                <form action="<?= e(url_for('admin/save-theme')) ?>" method="post" class="theme-form">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="theme_action" value="save">

                    <label class="theme-field">
                        <span>Page background</span>
                        <input type="color" name="theme_background_color" id="theme_background_color" value="<?= e((string) $themeSettings['background']) ?>" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                    </label>

                    <label class="theme-field">
                        <span>Surface color</span>
                        <input type="color" name="theme_surface_color" id="theme_surface_color" value="<?= e((string) $themeSettings['surface']) ?>" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                    </label>

                    <label class="theme-field">
                        <span>Text color</span>
                        <input type="color" name="theme_text_color" id="theme_text_color" value="<?= e((string) $themeSettings['text']) ?>" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                    </label>

                    <label class="theme-field">
                        <span>Accent color</span>
                        <input type="color" name="theme_primary_color" id="theme_primary_color" value="<?= e((string) $themeSettings['primary']) ?>" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                    </label>

                    <label class="theme-field">
                        <span>Neutral accent</span>
                        <input type="color" name="theme_secondary_color" id="theme_secondary_color" value="<?= e((string) $themeSettings['secondary']) ?>" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                    </label>

                    <label class="theme-field">
                        <span>Theme mode</span>
                        <select name="theme_mode" id="theme_mode" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                            <option value="light" <?= (string) $themeSettings['mode'] === 'light' ? 'selected' : '' ?>>Light</option>
                            <option value="dark" <?= (string) $themeSettings['mode'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                        </select>
                    </label>

                    <label class="theme-field">
                        <span>Font preset</span>
                        <select name="theme_font_preset" id="theme_font_preset" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                            <option value="inter" <?= (string) $themeSettings['font_preset'] === 'inter' ? 'selected' : '' ?>>Inter</option>
                            <option value="manrope" <?= (string) $themeSettings['font_preset'] === 'manrope' ? 'selected' : '' ?>>Manrope</option>
                            <option value="poppins" <?= (string) $themeSettings['font_preset'] === 'poppins' ? 'selected' : '' ?>>Poppins</option>
                            <option value="helvetica" <?= (string) $themeSettings['font_preset'] === 'helvetica' ? 'selected' : '' ?>>Helvetica</option>
                        </select>
                    </label>

                    <label class="theme-field">
                        <span>Spacing scale</span>
                        <select name="theme_spacing_scale" id="theme_spacing_scale" <?= !$siteSettingsReady ? 'disabled' : '' ?>>
                            <option value="compact" <?= (string) $themeSettings['spacing_scale'] === 'compact' ? 'selected' : '' ?>>Compact</option>
                            <option value="normal" <?= (string) $themeSettings['spacing_scale'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="spacious" <?= (string) $themeSettings['spacing_scale'] === 'spacious' ? 'selected' : '' ?>>Spacious</option>
                        </select>
                    </label>

                    <div class="theme-form-actions">
                        <button type="submit" class="button" <?= !$siteSettingsReady ? 'disabled' : '' ?>>Save theme</button>
                    </div>
                </form>

                <form action="<?= e(url_for('admin/save-theme')) ?>" method="post" class="theme-reset-form">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="theme_action" value="reset">
                    <button type="submit" class="button button-secondary" <?= !$siteSettingsReady ? 'disabled' : '' ?>>Reset to default</button>
                </form>
            </article>
        </div>

        <section class="theme-card">
            <div class="theme-card-head">
                <h2>Live Preview</h2>
                <p>Preview the selected palette and rhythm before saving.</p>
            </div>

            <div
                class="theme-preview"
                id="theme-preview"
                data-mode="<?= e((string) $themeSettings['mode']) ?>"
                style="
                    --preview-bg: <?= e((string) $themeSettings['background']) ?>;
                    --preview-surface: <?= e((string) $themeSettings['surface']) ?>;
                    --preview-text: <?= e((string) $themeSettings['text']) ?>;
                    --preview-primary: <?= e((string) $themeSettings['primary']) ?>;
                    --preview-secondary: <?= e((string) $themeSettings['secondary']) ?>;
                "
            >
                <div class="theme-preview-topbar">
                    <span class="theme-preview-badge">Preview</span>
                    <nav>
                        <span>Home</span>
                        <span>Dashboard</span>
                        <span>Login</span>
                    </nav>
                </div>
                <div class="theme-preview-content">
                    <div>
                        <p class="theme-preview-kicker">Brand surface</p>
                        <h3>Database-driven visual settings</h3>
                        <p>Buttons, cards, spacing, and typography all pick up the selected settings automatically.</p>
                    </div>
                    <div class="theme-preview-card">
                        <strong>Theme mode</strong>
                        <span id="theme-preview-mode-label"><?= e(ucfirst((string) $themeSettings['mode'])) ?></span>
                        <span id="theme-preview-font-label"><?= e((string) $themeSettings['font_label']) ?></span>
                        <span id="theme-preview-spacing-label"><?= e(ucfirst((string) $themeSettings['spacing_scale'])) ?> spacing</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="theme-card">
            <div class="theme-card-head">
                <h2>Current Background Assignments</h2>
                <p>Saved page backgrounds are stored under keys that begin with <span class="text-mono">bg_</span>.</p>
            </div>

            <div class="theme-background-list">
                <?php foreach ($backgrounds as $key => $value): ?>
                    <article class="theme-background-item">
                        <strong><?= e((string) $key) ?></strong>
                        <span><?= trim((string) $value) !== '' ? e((string) $value) : 'No background uploaded yet' ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var target = document.getElementById('page_target');
    var customWrap = document.getElementById('custom_page_wrap');
    var background = document.getElementById('theme_background_color');
    var surface = document.getElementById('theme_surface_color');
    var text = document.getElementById('theme_text_color');
    var primary = document.getElementById('theme_primary_color');
    var secondary = document.getElementById('theme_secondary_color');
    var mode = document.getElementById('theme_mode');
    var fontPreset = document.getElementById('theme_font_preset');
    var spacingScale = document.getElementById('theme_spacing_scale');
    var preview = document.getElementById('theme-preview');
    var modeLabel = document.getElementById('theme-preview-mode-label');
    var fontLabel = document.getElementById('theme-preview-font-label');
    var spacingLabel = document.getElementById('theme-preview-spacing-label');

    function toggleCustomPage() {
        if (!target || !customWrap) {
            return;
        }

        customWrap.hidden = target.value !== 'other';
    }

    function applyPreview() {
        if (!preview || !background || !surface || !text || !primary || !secondary || !mode) {
            return;
        }

        preview.style.setProperty('--preview-bg', background.value);
        preview.style.setProperty('--preview-surface', surface.value);
        preview.style.setProperty('--preview-text', text.value);
        preview.style.setProperty('--preview-primary', primary.value);
        preview.style.setProperty('--preview-secondary', secondary.value);
        preview.setAttribute('data-mode', mode.value);

        if (modeLabel) {
            modeLabel.textContent = mode.value.charAt(0).toUpperCase() + mode.value.slice(1);
        }

        if (fontLabel && fontPreset) {
            fontLabel.textContent = fontPreset.options[fontPreset.selectedIndex].text;
        }

        if (spacingLabel && spacingScale) {
            spacingLabel.textContent = spacingScale.options[spacingScale.selectedIndex].text + ' spacing';
        }
    }

    if (target) {
        target.addEventListener('change', toggleCustomPage);
        toggleCustomPage();
    }

    [background, surface, text, primary, secondary, mode, fontPreset, spacingScale].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener('input', applyPreview);
        field.addEventListener('change', applyPreview);
    });

    applyPreview();
});
</script>
