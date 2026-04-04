<?php
$role = (string) $profile['role'];
$roleLabel = match ($role) {
    'tasker' => 'Tasker profile',
    'client' => 'Client profile',
    'admin' => 'Admin profile',
    default => 'Marketplace profile',
};
$locationParts = array_values(array_filter([
    (string) ($profile['city'] ?? ''),
    (string) ($profile['region'] ?? ''),
    (string) ($profile['country'] ?? ''),
], static fn (string $value): bool => trim($value) !== ''));
$locationLabel = $locationParts !== [] ? implode(', ', $locationParts) : 'Location not added yet';
$avatarUrl = !empty($profile['avatar_path']) ? public_url((string) $profile['avatar_path']) : null;
$displayName = (string) $profile['full_name'];
$avatarInitial = strtoupper(substr($displayName !== '' ? $displayName : 'U', 0, 1));
$profileStats = is_array($profileStats ?? null) ? $profileStats : [];
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'My Profile';
        $eyebrow = 'Account';
        $intro = 'Keep your public details clear, your Rwanda location accurate, and your profile ready for demos or live usage.';
        $primaryAction = ['label' => 'Edit profile', 'href' => url_for('profile/edit')];
        unset($secondaryAction, $secondaryLink);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="profile-shell">
            <section class="profile-hero">
                <div class="profile-identity">
                    <div class="profile-avatar-wrap">
                        <?php if ($avatarUrl !== null): ?>
                            <button
                                type="button"
                                class="profile-avatar-button"
                                data-avatar-modal-src="<?= e($avatarUrl) ?>"
                                data-avatar-modal-alt="<?= e($displayName !== '' ? $displayName : 'Profile avatar') ?>"
                                aria-label="Open profile photo"
                            >
                                <img class="profile-avatar-xl" src="<?= e($avatarUrl) ?>" alt="<?= e($displayName) ?> avatar">
                            </button>
                        <?php else: ?>
                            <div class="profile-avatar-xl profile-avatar-fallback profile-avatar-xl-fallback"><?= e($avatarInitial) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-identity-copy">
                        <div class="button-group">
                            <span class="pill pill-success"><?= e($roleLabel) ?></span>
                            <?php if ($role === 'tasker'): ?>
                                <span class="pill pill-info">Visible to clients</span>
                            <?php elseif ($role === 'client'): ?>
                                <span class="pill pill-primary">Posting tasks in Rwanda</span>
                            <?php else: ?>
                                <span class="pill">Internal admin workspace</span>
                            <?php endif; ?>
                        </div>

                        <div class="profile-heading-block">
                            <h2><?= e($displayName) ?></h2>
                            <p class="muted"><?= e($locationLabel) ?></p>
                        </div>

                        <div class="profile-meta-grid">
                            <div class="profile-meta-item">
                                <span class="sidebar-item-label">Email</span>
                                <div class="sidebar-item-value"><?= e((string) $profile['email']) ?></div>
                            </div>
                            <div class="profile-meta-item">
                                <span class="sidebar-item-label">Phone</span>
                                <div class="sidebar-item-value">
                                    <?= !empty($profile['phone']) ? e((string) $profile['phone']) : 'Not set yet' ?>
                                </div>
                            </div>
                            <div class="profile-meta-item">
                                <span class="sidebar-item-label">Country</span>
                                <div class="sidebar-item-value"><?= !empty($profile['country']) ? e((string) $profile['country']) : 'Rwanda' ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($profileStats !== []): ?>
                    <div class="summary-grid">
                        <?php foreach ($profileStats as $stat): ?>
                            <article class="info-card task-summary-card">
                                <span class="sidebar-item-label"><?= e((string) ($stat['label'] ?? 'Stat')) ?></span>
                                <div class="task-summary-metric-row">
                                    <strong><?= e((string) ($stat['value'] ?? '')) ?></strong>
                                    <span><?= e((string) ($stat['note'] ?? '')) ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="profile-content-grid">
                <article class="sidebar-card">
                    <span class="sidebar-item-label">About</span>
                    <?php if (!empty($profile['bio'])): ?>
                        <div class="profile-rich-copy"><?= nl2br(e((string) $profile['bio'])) ?></div>
                    <?php else: ?>
                        <p class="muted">No biography added yet. A short, confident summary helps the profile feel complete and trustworthy.</p>
                    <?php endif; ?>
                </article>

                <article class="sidebar-card">
                    <span class="sidebar-item-label">Skills and summary</span>
                    <?php if (!empty($profile['skills_summary'])): ?>
                        <div class="profile-rich-copy"><?= nl2br(e((string) $profile['skills_summary'])) ?></div>
                    <?php else: ?>
                        <p class="muted">No skills summary yet. Add the kinds of jobs you handle best so the account looks demo-ready.</p>
                    <?php endif; ?>
                </article>
            </div>

            <?php if ($role === 'tasker'): ?>
                <section class="sidebar-card profile-share-card">
                    <div>
                        <span class="sidebar-item-label">Public profile</span>
                        <strong>Share your tasker page with clients</strong>
                        <p class="muted">Use this public profile when you want clients to review your work history, ratings, and profile details before booking.</p>
                    </div>
                    <div class="button-group">
                        <a class="button" href="<?= e(url_for('profile/view', ['id' => (int) $profile['user_id']])) ?>">Open public profile</a>
                        <code class="entity-chip text-mono"><?= e(url_for('profile/view', ['id' => (int) $profile['user_id']])) ?></code>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </section>
</div>
