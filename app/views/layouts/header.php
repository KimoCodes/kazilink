<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0e3b43">
    <title><?= e(($pageTitle ?? app_config('name')) . ' | ' . app_config('name')) ?></title>
    <meta name="description" content="Kazilink helps households and teams in Kigali book reliable local support with clearer pricing, better communication, and secure checkout.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
</head>
<body class="site-body">
<a class="skip-link" href="#main-content">Skip to content</a>
<?php
$currentUser = Auth::user();
$currentRoute = current_route();
$notifications = ['messages' => 0, 'bids' => 0];
$recentMessages = [];

if ($currentUser !== null) {
    $messageModel = new Message();
    $bidModel = new Bid();

    $role = (string) Auth::role();
    $notifications['messages'] = $messageModel->countUnreadForUser((int) Auth::id(), $role);
    $recentMessages = $messageModel->getRecentForUser((int) Auth::id(), $role, 5);

    if ($role === 'client') {
        $notifications['bids'] = $bidModel->countPendingForClient((int) Auth::id());
    }
}

$marketingLinks = [
    ['route' => 'home/index', 'label' => 'Home'],
    ['route' => 'marketing/about', 'label' => 'About'],
    ['route' => 'marketing/pricing', 'label' => 'Pricing'],
    ['route' => 'marketing/contact', 'label' => 'Contact'],
];

$workspaceLinks = [];

if (Auth::check()) {
    if (Auth::role() === 'client') {
        $workspaceLinks[] = ['route' => 'tasks/index', 'label' => 'My Tasks', 'badge' => $notifications['bids']];
        $workspaceLinks[] = ['route' => 'tasks/create', 'label' => 'Post a Task'];
    }

    if (in_array((string) Auth::role(), ['tasker', 'admin'], true)) {
        $workspaceLinks[] = ['route' => 'tasks/browse', 'label' => 'Browse Tasks'];
    }

    if (Auth::role() === 'tasker') {
        $workspaceLinks[] = ['route' => 'tasker/dashboard', 'label' => 'Dashboard'];
    }

    if (Auth::role() === 'admin') {
        $workspaceLinks[] = ['route' => 'admin/dashboard', 'label' => 'Admin'];
    }
}
?>
<div class="site-shell">
<header class="site-header">
    <div class="container shell-container">
        <div class="site-header-inner">
            <a class="brand brand-wrap" href="<?= e(url_for('home/index')) ?>" aria-label="<?= e(app_config('name')) ?> home">
                <span class="brand-mark">K</span>
                <span class="brand-copy">
                    <strong><?= e(app_config('name')) ?></strong>
                    <span>Trusted local coordination for homes, teams, and everyday tasks</span>
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

                        <?php if ($workspaceLinks !== [] || in_array((string) Auth::role(), ['client', 'tasker', 'admin'], true)): ?>
                            <ul class="nav-list nav-list-workspace">
                                <?php foreach ($workspaceLinks as $link): ?>
                                    <?php $isActive = route_is($link['route']); ?>
                                    <li class="nav-item-with-badge">
                                        <a
                                            href="<?= e(url_for($link['route'])) ?>"
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

                                <?php if (in_array((string) Auth::role(), ['client', 'tasker', 'admin'], true)): ?>
                                    <li class="nav-dropdown" aria-expanded="false">
                                        <button
                                            type="button"
                                            class="nav-dropdown-toggle"
                                            aria-expanded="false"
                                            aria-haspopup="true"
                                            aria-controls="nav-messages-panel"
                                        >
                                            <span>Messages</span>
                                            <?php if ($notifications['messages'] > 0): ?>
                                                <span class="nav-badge nav-badge-danger">
                                                    <?= min($notifications['messages'], 99) ?><?= $notifications['messages'] > 99 ? '+' : '' ?>
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                        <div class="nav-dropdown-panel" id="nav-messages-panel" role="menu" aria-hidden="true">
                                            <div class="nav-dropdown-header">
                                                <h3>Recent messages</h3>
                                                <a href="<?= e(url_for('bookings/index')) ?>" class="nav-dropdown-link">Open bookings</a>
                                            </div>
                                            <?php if (!empty($recentMessages)): ?>
                                                <ul class="nav-message-list">
                                                    <?php foreach ($recentMessages as $message): ?>
                                                        <li class="nav-message-item">
                                                            <a href="<?= e(url_for('messages/thread', ['id' => $message['booking_id']])) ?>" class="nav-message-link">
                                                                <div class="nav-message-sender">
                                                                    <strong><?= e($message['sender_name']) ?></strong>
                                                                    <span class="nav-message-time"><?= e(date('M j, g:i A', strtotime($message['created_at']))) ?></span>
                                                                </div>
                                                                <div class="nav-message-task">
                                                                    <span class="nav-message-task-title"><?= e(substr($message['task_title'], 0, 40)) ?><?= strlen($message['task_title']) > 40 ? '...' : '' ?></span>
                                                                </div>
                                                                <div class="nav-message-body">
                                                                    <?= e(substr($message['body'], 0, 60)) ?><?= strlen($message['body']) > 60 ? '...' : '' ?>
                                                                </div>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="nav-dropdown-empty">
                                                    <p>No recent messages yet.</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endif; ?>
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
                        </div>
                        <a class="button button-secondary button-small" href="<?= e(url_for('profile/show')) ?>">Profile</a>
                        <form method="post" action="<?= e(url_for('auth/logout')) ?>" class="nav-inline-form">
                            <?= Csrf::input() ?>
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
