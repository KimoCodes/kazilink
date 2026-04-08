<?php
$ads = is_array($ads ?? null) ? $ads : [];
if ($ads === []) {
    return;
}
?>
<section class="panel panel-subtle ad-banner-shell">
    <div class="section-head">
        <div>
            <span class="eyebrow">Featured</span>
            <h2>Announcements and promotions</h2>
            <p class="section-intro">Published by the admin team to highlight timely offers, campaigns, or important updates.</p>
        </div>
    </div>

    <div class="marketing-grid marketing-grid-three">
        <?php foreach ($ads as $ad): ?>
            <?php
            $mediaUrls = !empty($ad['media_path']) ? public_url_candidates((string) $ad['media_path']) : [];
            $primaryMediaUrl = $mediaUrls[0] ?? null;
            $fallbackMediaUrl = $mediaUrls[1] ?? null;
            ?>
            <article class="feature-card ad-banner-card">
                <?php if ($primaryMediaUrl !== null && (string) ($ad['media_type'] ?? '') === 'image'): ?>
                    <img
                        class="ad-banner-media"
                        src="<?= e($primaryMediaUrl) ?>"
                        alt="<?= e((string) $ad['title']) ?>"
                        <?php if ($fallbackMediaUrl !== null): ?>
                            data-fallback-src="<?= e($fallbackMediaUrl) ?>"
                            onerror="if(this.dataset.fallbackSrc&&this.src!==this.dataset.fallbackSrc){this.src=this.dataset.fallbackSrc;}"
                        <?php endif; ?>
                    >
                <?php elseif ($primaryMediaUrl !== null && (string) ($ad['media_type'] ?? '') === 'video'): ?>
                    <video class="ad-banner-media" controls preload="metadata">
                        <source src="<?= e($primaryMediaUrl) ?>">
                        <?php if ($fallbackMediaUrl !== null): ?>
                            <source src="<?= e($fallbackMediaUrl) ?>">
                        <?php endif; ?>
                        Your browser does not support inline video playback.
                    </video>
                <?php endif; ?>
                <h3><?= e((string) $ad['title']) ?></h3>
                <p class="muted"><?= nl2br(e((string) $ad['body'])) ?></p>
                <?php if (!empty($ad['cta_label']) && !empty($ad['cta_url'])): ?>
                    <a class="button-link" href="<?= e((string) $ad['cta_url']) ?>"><?= e((string) $ad['cta_label']) ?></a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
