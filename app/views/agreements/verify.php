<div class="container">
    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">Agreement verification</span>
            <h1><?= $agreement !== null ? 'Hiring Agreement verified' : 'Agreement not found' ?></h1>
            <p class="page-intro">
                <?= $agreement !== null
                    ? e(app_config('name')) . ' has a matching hiring agreement record for the provided identifier.'
                    : 'No public hiring agreement record matches that identifier.' ?>
            </p>
        </div>
    </section>

    <section class="panel panel-subtle">
        <?php if ($agreement === null): ?>
            <p class="muted">Check the agreement UID and try again.</p>
        <?php else: ?>
            <div class="detail-facts">
                <div class="detail-fact">
                    <span class="detail-fact-label">Verification</span>
                    <div class="detail-fact-value"><span class="pill pill-success">Valid record</span></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Issued</span>
                    <div class="detail-fact-value"><?= e(dateFmt((string) $agreement['created_at'])) ?></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Public note</span>
                    <div class="detail-fact-value">Non-sensitive fields only</div>
                </div>
            </div>
            <div class="summary-grid">
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Agreement UID</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $agreement['agreement_uid']) ?></strong>
                        <span>Valid public record</span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Status</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e(agreement_status_label((string) $agreement['status'])) ?></strong>
                        <span>Last recorded agreement state</span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Job title</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $agreement['job_title']) ?></strong>
                        <span><?= e((string) $agreement['category']) ?></span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Location and start</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $agreement['location_text']) ?></strong>
                        <span><?= e(dateFmt((string) $agreement['start_datetime'])) ?></span>
                    </div>
                </article>
            </div>
            <article class="info-card">
                <h2>Verification statement</h2>
                <p class="muted"><?= e(app_config('name')) ?> confirms that this agreement identifier matches a hiring record created on the platform. This page intentionally omits private contact details and full agreement text.</p>
            </article>
        <?php endif; ?>
    </section>
</div>
