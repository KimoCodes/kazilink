<!DOCTYPE html>
<html lang="en">
<?php
$siteThemeStyleBlock = '';
$siteThemeBodyClass = 'site-body';
$siteThemeColor = '#0e5b57';
$siteThemeFontHref = '';

try {
    $siteSettingModel = new SiteSetting();
    $siteCurrentRoute = current_route();
    $siteThemeValues = $siteSettingModel->themeSettings();
    $siteBackground = $siteSettingModel->backgroundForRoute($siteCurrentRoute);
    $siteBackgroundUrl = trim((string) $siteBackground['path']) !== '' ? public_url((string) $siteBackground['path']) : '';
    $siteThemeColor = (string) ($siteThemeValues['primary'] ?? '#0e5b57');
    $siteThemeFontHref = (string) ($siteThemeValues['font_embed_href'] ?? '');
    $siteThemeBodyClass = implode(' ', array_filter([
        'site-body',
        'site-theme-enabled',
        'theme-mode-' . (string) ($siteThemeValues['mode'] ?? 'light'),
        'page-' . (string) ($siteBackground['page_name'] ?? 'home'),
        $siteBackgroundUrl !== '' ? 'has-dynamic-bg' : 'no-dynamic-bg',
    ]));

    $siteThemeCss = [
        ':root {',
        '    --primary: ' . (string) $siteThemeValues['primary'] . ';',
        '    --primary-strong: ' . (string) $siteThemeValues['primary_strong'] . ';',
        '    --primary-soft: ' . (string) $siteThemeValues['primary_soft'] . ';',
        '    --secondary: ' . (string) $siteThemeValues['secondary'] . ';',
        '    --secondary-strong: ' . (string) $siteThemeValues['secondary_strong'] . ';',
        '    --secondary-soft: ' . (string) $siteThemeValues['secondary_soft'] . ';',
        '    --color-bg: ' . (string) $siteThemeValues['background'] . ';',
        '    --color-surface: ' . (string) $siteThemeValues['surface'] . ';',
        '    --color-surface-muted: ' . (string) $siteThemeValues['surface_muted'] . ';',
        '    --color-surface-strong: ' . (string) $siteThemeValues['surface_strong'] . ';',
        '    --color-border: ' . (string) $siteThemeValues['border'] . ';',
        '    --color-border-strong: ' . (string) $siteThemeValues['border_strong'] . ';',
        '    --color-text: ' . (string) $siteThemeValues['text'] . ';',
        '    --color-text-soft: ' . (string) $siteThemeValues['text_soft'] . ';',
        '    --color-text-faint: ' . (string) $siteThemeValues['text_faint'] . ';',
        '    --color-text-muted: ' . (string) $siteThemeValues['text_soft'] . ';',
        '    --theme-surface-tint: ' . (string) $siteThemeValues['surface_tint'] . ';',
        '    --theme-focus-ring: ' . (string) $siteThemeValues['focus_ring'] . ';',
        '    --theme-font-sans: ' . (string) ($siteThemeValues['font_sans'] ?? '"Inter", "Helvetica Neue", Arial, sans-serif') . ';',
        '    --theme-font-display: ' . (string) ($siteThemeValues['font_display'] ?? '"Inter", "Helvetica Neue", Arial, sans-serif') . ';',
        '    --theme-section-space: ' . (string) ($siteThemeValues['section_space'] ?? '4.75rem') . ';',
        '    --theme-panel-padding: ' . (string) ($siteThemeValues['panel_padding'] ?? '1.7rem') . ';',
        '    --theme-shell-width: ' . (string) ($siteThemeValues['shell_width'] ?? '1140px') . ';',
        '    --page-bg-image: ' . ($siteBackgroundUrl !== '' ? "url('" . e($siteBackgroundUrl) . "')" : 'none') . ';',
        '    --color-primary: var(--primary);',
        '    --color-primary-strong: var(--primary-strong);',
        '    --color-primary-soft: var(--primary-soft);',
        '    --color-secondary: var(--secondary);',
        '    --color-secondary-strong: var(--secondary-strong);',
        '    --focus-ring: 0 0 0 4px var(--theme-focus-ring);',
        '}',
    ];

    if (($siteThemeValues['mode'] ?? 'light') === 'dark') {
        $siteThemeCss[] = 'body.theme-mode-dark {';
        $siteThemeCss[] = '    --color-success-soft: rgba(35, 90, 58, 0.45);';
        $siteThemeCss[] = '    --color-warning-soft: rgba(104, 72, 31, 0.4);';
        $siteThemeCss[] = '    --color-danger-soft: rgba(118, 51, 55, 0.42);';
        $siteThemeCss[] = '    --color-info-soft: rgba(43, 84, 118, 0.38);';
        $siteThemeCss[] = '    --color-shadow: 0 28px 80px rgba(0, 0, 0, 0.22);';
        $siteThemeCss[] = '}';
    } else {
        $siteThemeCss[] = 'body.theme-mode-light {';
        $siteThemeCss[] = '    --color-surface-strong: var(--secondary);';
        $siteThemeCss[] = '}';
    }

    $siteThemeStyleBlock = '<style>' . implode('', $siteThemeCss) . '</style>';
} catch (Throwable $exception) {
    $siteThemeStyleBlock = '';
    $siteThemeBodyClass = 'site-body';
    $siteThemeColor = '#0e5b57';
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?= e($siteThemeColor) ?>">
    <title><?= e(($pageTitle ?? app_config('name')) . ' | ' . app_config('name')) ?></title>
    <meta name="description" content="Kazilink helps households and teams hire reliable local support, pay offline, and keep a clear hiring agreement with dispute evidence.">
    <?php if ($siteThemeFontHref !== ''): ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="<?= e($siteThemeFontHref) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/theme-system.css')) ?>">
    <?= $siteThemeStyleBlock ?>
</head>
<?php
$currentUser = Auth::user();
$notifications = ['messages' => 0, 'bids' => 0];
$subscriptionSummary = null;

if ($currentUser !== null) {
    $messageModel = new Message();
    $bidModel = new Bid();

    $role = (string) Auth::role();
    $notifications['messages'] = $messageModel->countUnreadForUser((int) Auth::id(), $role);

    if ($role === 'client') {
        $notifications['bids'] = $bidModel->countPendingForClient((int) Auth::id());
    }

    if ($role !== 'admin') {
        $subscriptionSummary = SubscriptionAccess::summaryForUser((int) Auth::id());
    }
}

$marketingLinks = Auth::check()
    ? []
    : [
        ['route' => 'home/index', 'label' => 'Home'],
        ['route' => 'marketing/about', 'label' => 'About'],
        ['route' => 'marketing/pricing', 'label' => 'How It Works'],
        ['route' => 'marketing/contact', 'label' => 'Contact'],
    ];

$workspaceLinks = [];

if (Auth::check()) {
    if (Auth::role() === 'client') {
        $workspaceLinks[] = ['route' => 'tasks/index', 'label' => 'My Tasks', 'badge' => $notifications['bids']];
        $workspaceLinks[] = ['route' => 'tasks/create', 'label' => 'Post a Task'];
        $workspaceLinks[] = ['route' => 'subscriptions/*', 'label' => 'Subscription', 'href' => url_for('subscriptions/index')];
        $workspaceLinks[] = ['route' => 'tasks/index', 'label' => 'Find Taskers', 'href' => url_for('tasks/index') . '#available-taskers'];
        $workspaceLinks[] = ['route' => 'marketplace/*', 'label' => 'Marketplace', 'href' => url_for('marketplace/index')];
        $workspaceLinks[] = ['route' => 'marketplace/create', 'label' => 'Sell Item'];
        $workspaceLinks[] = ['route' => 'messages/*', 'label' => 'Inbox', 'badge' => $notifications['messages'], 'href' => url_for('messages/index')];
    }

    if (in_array((string) Auth::role(), ['tasker', 'admin'], true)) {
        $workspaceLinks[] = ['route' => 'tasks/browse', 'label' => 'Browse Tasks'];
        $workspaceLinks[] = ['route' => 'marketplace/*', 'label' => 'Marketplace', 'href' => url_for('marketplace/index')];
        $workspaceLinks[] = ['route' => 'marketplace/create', 'label' => 'Sell Item'];
    }

    if (Auth::role() === 'tasker') {
        $workspaceLinks[] = ['route' => 'subscriptions/*', 'label' => 'Subscription', 'href' => url_for('subscriptions/index')];
        $workspaceLinks[] = ['route' => 'tasker/dashboard', 'label' => 'Dashboard'];
        $workspaceLinks[] = ['route' => 'messages/*', 'label' => 'Inbox', 'badge' => $notifications['messages'], 'href' => url_for('messages/index')];
    }

    if (Auth::role() === 'admin') {
        $workspaceLinks[] = ['route' => 'admin/dashboard', 'label' => 'Admin'];
        $workspaceLinks[] = ['route' => 'admin/messages', 'label' => 'Messages'];
        $workspaceLinks[] = ['route' => 'admin/newsletter', 'label' => 'Subscribers'];
    }
}
?>
<body
    class="<?= e($siteThemeBodyClass) ?>"
    <?php if ($currentUser !== null): ?>
        data-session-timeout-seconds="<?= e((string) session_idle_timeout_seconds()) ?>"
        data-session-heartbeat-seconds="<?= e((string) session_heartbeat_interval_seconds()) ?>"
        data-session-ping-url="<?= e(url_for('auth/ping')) ?>"
    <?php endif; ?>
>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="site-shell">
<header class="site-header">
    <div class="container shell-container">
        <div class="site-header-inner">
            <a class="brand brand-wrap" href="<?= e(url_for('home/index')) ?>" aria-label="<?= e(app_config('name')) ?> home">
                <span class="brand-mark">K</span>
                <span class="brand-copy">
                    <strong><?= e(app_config('name')) ?></strong>
                    <span>Premium clarity for local hiring</span>
                </span>
            </a>

            <button
                type="button"
                class="nav-toggle"
                data-nav-toggle
                aria-expanded="false"
                aria-controls="primary-nav"
            >
                <span></span>
                <span></span>
                <span></span>
                <span class="sr-only">Toggle navigation</span>
            </button>

            <div class="site-nav-shell" id="primary-nav" data-nav-shell>
                <nav class="site-nav" aria-label="Primary navigation">
                    <div class="nav-groups">
                        <?php if ($marketingLinks !== []): ?>
                            <ul class="nav-list nav-list-marketing">
                                <?php foreach ($marketingLinks as $link): ?>
                                    <?php $isActive = route_is($link['route']); ?>
                                    <li>
                                        <a
                                            href="<?= e(url_for($link['route'])) ?>"
                                            class="<?= $isActive ? 'nav-active' : '' ?>"
                                            <?= $isActive ? 'aria-current="page"' : '' ?>
                                        >
                                            <?= e($link['label']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($workspaceLinks !== [] || in_array((string) Auth::role(), ['client', 'tasker', 'admin'], true)): ?>
                            <ul class="nav-list nav-list-workspace">
                                <?php foreach ($workspaceLinks as $link): ?>
                                    <?php $isActive = route_is($link['route']); ?>
                                    <li class="nav-item-with-badge">
                                        <a
                                            href="<?= e((string) ($link['href'] ?? url_for($link['route']))) ?>"
                                            class="<?= $isActive ? 'nav-active' : '' ?>"
                                            <?= $isActive ? 'aria-current="page"' : '' ?>
                                        >
                                            <?= e($link['label']) ?>
                                        </a>
                                        <?php if ((int) ($link['badge'] ?? 0) > 0): ?>
                                            <span class="nav-badge nav-badge-warning">
                                                <?= min((int) $link['badge'], 99) ?><?= (int) $link['badge'] > 99 ? '+' : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>

                            </ul>
                        <?php endif; ?>
                    </div>
                </nav>

                <div class="nav-actions">
                    <?php if ($currentUser !== null): ?>
                        <div class="nav-user-card">
                            <span class="nav-user-label">Signed in</span>
                            <strong><?= e($currentUser['full_name'] !== '' ? $currentUser['full_name'] : $currentUser['email']) ?></strong>
                            <span><?= e(ucfirst((string) Auth::role())) ?></span>
                            <?php if (is_array($subscriptionSummary)): ?>
                                <span><?= e($subscriptionSummary['plan_name']) ?> plan<?= $subscriptionSummary['has_access'] ? '' : ' • renewal needed' ?></span>
                            <?php endif; ?>
                        </div>
                        <a class="button button-secondary button-small" href="<?= e(url_for('profile/show')) ?>">Profile</a>
                        <form method="post" action="<?= e(url_for('auth/logout')) ?>" class="nav-inline-form" data-session-logout-form>
                            <?= Csrf::input() ?>
                            <input type="hidden" name="logout_reason" value="">
                            <button type="submit" class="button button-secondary button-small" data-confirm="Log out now?">Log out</button>
                        </form>
                    <?php else: ?>
                        <a class="button button-secondary button-small" href="<?= e(url_for('auth/login')) ?>">Log in</a>
                        <a class="button button-small" href="<?= e(url_for('auth/register')) ?>">Create account</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>
<main class="main-content" id="main-content">
    <div class="container shell-container">
        <?php require BASE_PATH . '/app/views/partials/flash.php'; ?>
        <?php if (isset($errors) && is_array($errors)): ?>
            <?php require BASE_PATH . '/app/views/partials/errors.php'; ?>
        <?php endif; ?>
    </div>
